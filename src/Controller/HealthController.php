<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sonde légère pour l’ELB / Application Load Balancer (pas de BDD, pas de session).
 */
final class HealthController
{
    #[Route('/health', name: 'health_check', methods: ['GET', 'HEAD'])]
    public function __invoke(): Response
    {
        return new Response('ok', Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
