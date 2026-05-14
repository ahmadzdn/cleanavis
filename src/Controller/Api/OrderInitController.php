<?php

namespace App\Controller\Api;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use App\Repository\PackOfferRepository;
use App\Service\TurnstileVerifier;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/order')]
final class OrderInitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerOrderRepository $orderRepository,
        private readonly PackOfferRepository $packOfferRepository,
        private readonly StripeCheckoutService $stripeCheckoutService,
        private readonly TurnstileVerifier $turnstileVerifier,
        private readonly ValidatorInterface $validator,
        private readonly bool $skipCaptcha,
    ) {
    }

    #[Route('/init', name: 'api_order_init', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $payload */
        $payload = array_merge([
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'phone' => '',
            'company' => '',
            'address' => '',
            'reviewUrl' => '',
            'package' => '',
            'justification' => '',
            'turnstileToken' => '',
        ], $payload);

        $constraints = new Assert\Collection([
            'fields' => [
                'firstName' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
                'lastName' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'phone' => [new Assert\NotBlank(), new Assert\Length(max: 40)],
                'company' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
                'address' => new Assert\Optional([new Assert\Length(max: 255)]),
                'reviewUrl' => [new Assert\NotBlank(), new Assert\Url(requireTld: false), new Assert\Length(max: 2048)],
                'package' => [new Assert\NotBlank()],
                'justification' => [new Assert\NotBlank()],
                'turnstileToken' => new Assert\Optional([new Assert\Length(min: 1)]),
            ],
            'allowMissingFields' => false,
            'allowExtraFields' => false,
        ]);

        $violations = $this->validator->validate($payload, $constraints);
        if (\count($violations) > 0) {
            return $this->json(['error' => (string) $violations->get(0)->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $packOffer = $this->packOfferRepository->findOneEnabledByFormSlug((string) $payload['package']);
        if (!$packOffer) {
            return $this->json(['error' => 'Package inconnu ou indisponible.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->skipCaptcha) {
            $token = $payload['turnstileToken'] ?? '';
            if (!$this->turnstileVerifier->verify($token, $request->getClientIp())) {
                return $this->json(['error' => 'Validation anti-robot échouée.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $order = new CustomerOrder();
        $order->setReference($this->generateUniqueReference());
        $order->setFirstName($payload['firstName']);
        $order->setLastName($payload['lastName']);
        $order->setEmail($payload['email']);
        $order->setPhone($payload['phone']);
        $order->setCompany($payload['company']);
        $order->setAddress($payload['address'] ?? null);
        $order->setReviewUrl($payload['reviewUrl']);
        $order->setPackKey($packOffer->getInternalKey());
        $order->setJustification($payload['justification']);
        $order->setStatus(CustomerOrder::STATUS_PENDING);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        try {
            $session = $this->stripeCheckoutService->createSessionForOrder($order);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Impossible de démarrer le paiement. '.$e->getMessage()],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $order->setStripeCheckoutSessionId($session->id);
        $this->entityManager->flush();

        $clientSecret = $session->client_secret ?? null;
        if (!\is_string($clientSecret) || $clientSecret === '') {
            return $this->json(
                ['error' => 'Session Stripe incomplète (client_secret manquant). Vérifiez la configuration Embedded Checkout.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        return $this->json([
            'reference' => $order->getReference(),
            'clientSecret' => $clientSecret,
        ]);
    }

    private function generateUniqueReference(): string
    {
        for ($i = 0; $i < 8; ++$i) {
            $ref = 'CA-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            if (null === $this->orderRepository->findOneByReference($ref)) {
                return $ref;
            }
        }

        throw new \RuntimeException('Impossible de générer une référence unique.');
    }
}
