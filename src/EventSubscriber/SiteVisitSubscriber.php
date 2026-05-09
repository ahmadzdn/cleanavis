<?php

namespace App\EventSubscriber;

use App\Entity\SiteVisit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enregistre une ligne par vue « site public » (routes listées) après envoi de la réponse.
 */
final class SiteVisitSubscriber implements EventSubscriberInterface
{
    /** Routes GET à compter comme visites (pages marketing / tunnel paiement). */
    private const ROUTE_PREFIXES = ['app_', 'page_', 'payment_'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => ['onKernelTerminate', -10]];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethod('GET')) {
            return;
        }

        $route = $request->attributes->get('_route');
        if (!\is_string($route) || $route === '') {
            return;
        }

        if (!$this->isTrackedRoute($route)) {
            return;
        }

        $visit = new SiteVisit();
        $visit->setRouteName($route);
        try {
            $this->entityManager->persist($visit);
            $this->entityManager->flush();
        } catch (\Throwable) {
            // Ne pas altérer la réponse HTTP si l’enregistrement de la visite échoue (ex. lock BDD).
        }
    }

    private function isTrackedRoute(string $route): bool
    {
        foreach (self::ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
