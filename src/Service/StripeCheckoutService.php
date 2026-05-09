<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Repository\PackOfferRepository;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class StripeCheckoutService
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $secretKey,
        private readonly PackOfferRepository $packOfferRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createSessionForOrder(CustomerOrder $order): Session
    {
        Stripe::setApiKey($this->secretKey);

        $pack = $this->packOfferRepository->findOneEnabledByInternalKey($order->getPackKey());
        if (!$pack) {
            throw new \InvalidArgumentException('Pack inconnu ou désactivé.');
        }

        $successUrl = $this->urlGenerator->generate(
            'payment_success',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ).'?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $this->urlGenerator->generate(
            'payment_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return Session::create([
            'mode' => 'payment',
            'locale' => 'fr',
            'customer_email' => $order->getEmail(),
            'client_reference_id' => $order->getReference(),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $pack->getPriceAmountCents(),
                    'product_data' => [
                        'name' => $pack->getStripeDisplayName(),
                        'description' => $pack->getStripeDescription(),
                    ],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_ref' => $order->getReference(),
                'pack_key' => $order->getPackKey(),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_ref' => $order->getReference(),
                ],
            ],
        ]);
    }
}
