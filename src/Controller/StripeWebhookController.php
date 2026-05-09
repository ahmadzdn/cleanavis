<?php

namespace App\Controller;

use App\Service\StripeOrderCompletionService;
use Psr\Log\LoggerInterface;
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
        private readonly StripeOrderCompletionService $orderCompletionService,
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
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $this->orderCompletionService->completeOrderFromCheckoutSession($session);
        }

        return new Response(status: Response::HTTP_OK);
    }
}
