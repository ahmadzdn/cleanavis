<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactPageController extends AbstractController
{
    public function __construct(
        private readonly string $turnstileSiteKey,
    ) {
    }

    #[Route('/contact', name: 'page_contact', methods: ['GET'])]
    #[Route('/contact.html', name: 'page_contact_html', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('contact/contact.html.twig', [
            'turnstile_site_key' => $this->turnstileSiteKey,
        ]);
    }
}
