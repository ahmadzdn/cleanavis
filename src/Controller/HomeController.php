<?php

namespace App\Controller;

use App\Repository\PackOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly string $googleMapsApiKey,
        private readonly string $turnstileSiteKey,
        private readonly PackOfferRepository $packOfferRepository,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $packOffers = $this->packOfferRepository->findEnabledOrdered();

        return $this->render('home/index.html.twig', [
            'google_maps_api_key' => $this->googleMapsApiKey,
            'turnstile_site_key' => $this->turnstileSiteKey,
            'packOffers' => $packOffers,
            'packsJson' => $this->buildPacksJsonForJs($packOffers),
        ]);
    }

    /**
     * @param list<\App\Entity\PackOffer> $packs
     */
    private function buildPacksJsonForJs(array $packs): string
    {
        $out = [];
        foreach ($packs as $p) {
            $out[$p->getFormSlug()] = [
                'priceEuro' => (int) ($p->getPriceAmountCents() / 100),
                'cardTitle' => $p->getCardTitle(),
                'formSlug' => $p->getFormSlug(),
            ];
        }

        return json_encode($out, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
