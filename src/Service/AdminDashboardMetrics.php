<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Repository\ContactMessageRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\EmailLogRepository;
use App\Repository\SiteVisitRepository;

/**
 * Agrège les compteurs affichés sur le tableau de bord EasyAdmin.
 */
final class AdminDashboardMetrics
{
    public function __construct(
        private readonly SiteVisitRepository $siteVisitRepository,
        private readonly ContactMessageRepository $contactMessageRepository,
        private readonly CustomerOrderRepository $customerOrderRepository,
        private readonly EmailLogRepository $emailLogRepository,
    ) {
    }

    /** @return array<string, int|float> */
    public function getMetrics(): array
    {
        $now = new \DateTimeImmutable();
        $since30 = $now->modify('-30 days');

        $visitsTotal = $this->siteVisitRepository->countTotal();
        $visits30d = $this->siteVisitRepository->countSince($since30);

        $contactTotal = (int) $this->contactMessageRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $contactUnread = $this->contactMessageRepository->countUnread();

        $ordersPaid = $this->countOrders(CustomerOrder::STATUS_PAID);
        $ordersPending = $this->countOrders(CustomerOrder::STATUS_PENDING);
        $ordersFailed = $this->countOrders(CustomerOrder::STATUS_FAILED);
        $ordersCancelled = $this->countOrders(CustomerOrder::STATUS_CANCELLED);
        $ordersTotal = $ordersPaid + $ordersPending + $ordersFailed + $ordersCancelled;

        $emailsOk = (int) $this->emailLogRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.success = true')
            ->getQuery()
            ->getSingleScalarResult();
        $emailsKo = (int) $this->emailLogRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.success = false')
            ->getQuery()
            ->getSingleScalarResult();

        $revenueCents = (int) $this->customerOrderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.amountTotal), 0)')
            ->where('o.status = :paid')
            ->setParameter('paid', CustomerOrder::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'visits_total' => $visitsTotal,
            'visits_30d' => $visits30d,
            'contact_total' => $contactTotal,
            'contact_unread' => $contactUnread,
            'orders_total' => $ordersTotal,
            'orders_paid' => $ordersPaid,
            'orders_pending' => $ordersPending,
            'orders_failed' => $ordersFailed,
            'orders_cancelled' => $ordersCancelled,
            'emails_ok' => $emailsOk,
            'emails_ko' => $emailsKo,
            'revenue_euros' => round($revenueCents / 100, 2),
        ];
    }

    private function countOrders(string $status): int
    {
        return (int) $this->customerOrderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :s')
            ->setParameter('s', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
