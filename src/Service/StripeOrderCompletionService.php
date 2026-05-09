<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as CheckoutSession;

/**
 * Finalisation commande après paiement Stripe Checkout (webhook et/ou page /paiement/merci).
 */
final class StripeOrderCompletionService
{
    public function __construct(
        private readonly CustomerOrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Idempotent : si la commande est déjà payée, ne fait rien (pas de doublon d’e-mails).
     */
    public function completeOrderFromCheckoutSession(CheckoutSession $session): void
    {
        if (($session->payment_status ?? '') !== 'paid') {
            $this->logger->info('Session Stripe ignorée : paiement non confirmé.', ['session' => $session->id ?? null]);

            return;
        }

        $ref = $session->metadata['order_ref'] ?? null;
        if (!$ref || !\is_string($ref)) {
            $this->logger->warning('Stripe session sans order_ref', ['session' => $session->id ?? null]);

            return;
        }

        $order = $this->orderRepository->findOneByReference($ref);
        if (!$order) {
            $this->logger->warning('Commande introuvable pour référence '.$ref);

            return;
        }

        if ($order->getStatus() === CustomerOrder::STATUS_PAID) {
            return;
        }

        $order->setStatus(CustomerOrder::STATUS_PAID);
        $order->setPaidAt(new \DateTimeImmutable());
        $order->setAmountTotal($session->amount_total);
        if (\is_string($session->id) && $session->id !== '') {
            $order->setStripeCheckoutSessionId($session->id);
        }
        $pi = $session->payment_intent;
        if (\is_string($pi)) {
            $order->setStripePaymentIntentId($pi);
        } elseif (\is_object($pi) && isset($pi->id)) {
            $order->setStripePaymentIntentId((string) $pi->id);
        }

        $this->entityManager->flush();

        $this->logger->info('Commande marquée payée et e-mails de confirmation déclenchés.', ['reference' => $ref]);

        $this->notificationService->sendPurchaseConfirmation($order);
    }
}
