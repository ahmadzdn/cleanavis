<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
        private readonly CustomerOrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/webhooks/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        Stripe::setApiKey($this->stripeSecretKey);
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        if (!$sigHeader) {
            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (\Throwable $e) {
            $this->logger->warning('Stripe webhook signature failed: '.$e->getMessage());

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event);
        }

        return new Response(status: Response::HTTP_OK);
    }

    private function handleCheckoutCompleted(Event $event): void
    {
        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;
        $ref = $session->metadata['order_ref'] ?? null;
        if (!$ref) {
            $this->logger->warning('Stripe session sans order_ref');

            return;
        }

        $order = $this->orderRepository->findOneByReference($ref);
        if (!$order) {
            $this->logger->warning('Commande introuvable pour ref '.$ref);

            return;
        }

        if ($order->getStatus() === CustomerOrder::STATUS_PAID) {
            return;
        }

        $order->setStatus(CustomerOrder::STATUS_PAID);
        $order->setPaidAt(new \DateTimeImmutable());
        $order->setAmountTotal($session->amount_total);
        $pi = $session->payment_intent;
        if (\is_string($pi)) {
            $order->setStripePaymentIntentId($pi);
        } elseif (\is_object($pi) && isset($pi->id)) {
            $order->setStripePaymentIntentId((string) $pi->id);
        }

        $this->entityManager->flush();

        $this->notificationService->sendPurchaseConfirmation($order);
    }
}
