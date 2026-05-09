<?php

namespace App\Controller\Api;

use App\Entity\ContactMessage;
use App\Service\TurnstileVerifier;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/contact')]
final class ContactSubmitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TurnstileVerifier $turnstileVerifier,
        private readonly NotificationService $notificationService,
        private readonly ValidatorInterface $validator,
        private readonly bool $skipCaptcha,
    ) {
    }

    #[Route('/submit', name: 'api_contact_submit', methods: ['POST'])]
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
            'subject' => '',
            'message' => '',
            'turnstileToken' => '',
        ], $payload);

        $constraints = new Assert\Collection([
            'fields' => [
                'firstName' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
                'lastName' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'subject' => [new Assert\NotBlank(), new Assert\Length(max: 64)],
                'message' => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 8000)],
                'turnstileToken' => new Assert\Optional([new Assert\Length(min: 1)]),
            ],
            'allowMissingFields' => false,
            'allowExtraFields' => false,
        ]);

        $violations = $this->validator->validate($payload, $constraints);
        if (\count($violations) > 0) {
            return $this->json(['error' => (string) $violations->get(0)->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->skipCaptcha) {
            $token = $payload['turnstileToken'] ?? '';
            if (!$this->turnstileVerifier->verify($token, $request->getClientIp())) {
                return $this->json(['error' => 'Validation anti-robot échouée.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $entity = new ContactMessage();
        $entity->setFirstName($payload['firstName']);
        $entity->setLastName($payload['lastName']);
        $entity->setEmail($payload['email']);
        $entity->setSubject($payload['subject']);
        $entity->setMessage($payload['message']);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->notificationService->notifyNewContact($entity);

        return $this->json(['ok' => true]);
    }
}
