<?php

namespace App\Controller;

use App\Service\StripeOrderCompletionService;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeOrderCompletionService $orderCompletion,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey,
    ) {
    }

    #[Route('/paiement/merci', name: 'payment_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');
        if (\is_string($sessionId) && $sessionId !== '') {
            Stripe::setApiKey($this->stripeSecretKey);
            try {
                $session = Session::retrieve($sessionId);
                $this->orderCompletion->completeOrderFromCheckoutSession($session);
            } catch (\Throwable $e) {
                $this->logger->warning('Finalisation paiement depuis /paiement/merci impossible : '.$e->getMessage(), [
                    'session_id' => $sessionId,
                ]);
            }
        }

        return $this->render('payment/success.html.twig', [
            'sessionId' => $request->query->get('session_id'),
        ]);
    }

    #[Route('/paiement/annule', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }

    #[Route('/paiement/session-meta', name: 'payment_session_meta', methods: ['GET'])]
    public function sessionMeta(Request $request): JsonResponse
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId || !\is_string($sessionId)) {
            return $this->json(['reference' => null], Response::HTTP_BAD_REQUEST);
        }

        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $session = Session::retrieve($sessionId);
            $ref = $session->metadata['order_ref'] ?? null;

            return $this->json(['reference' => \is_string($ref) ? $ref : null]);
        } catch (\Throwable) {
            return $this->json(['reference' => null], Response::HTTP_NOT_FOUND);
        }
    }
}
