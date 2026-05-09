<?php

namespace App\Controller;

use App\Repository\PackOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    public function __construct(
        private readonly PackOfferRepository $packOfferRepository,
    ) {
    }

    #[Route('/cgs', name: 'page_cgs', methods: ['GET'])]
    public function cgs(): Response
    {
        return $this->render('page/cgs.html.twig', [
            'packOffers' => $this->packOfferRepository->findEnabledOrdered(),
        ]);
    }

    #[Route('/cgs.html', name: 'page_cgs_html', methods: ['GET'])]
    public function cgsLegacy(): Response
    {
        return $this->redirectToRoute('page_cgs', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
