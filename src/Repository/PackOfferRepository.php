<?php

namespace App\Repository;

use App\Entity\PackOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PackOffer>
 */
class PackOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackOffer::class);
    }

    /** @return list<PackOffer> */
    public function findEnabledOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.enabled = true')
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneEnabledByFormSlug(string $slug): ?PackOffer
    {
        return $this->createQueryBuilder('p')
            ->where('p.enabled = true')
            ->andWhere('p.formSlug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneEnabledByInternalKey(string $internalKey): ?PackOffer
    {
        return $this->createQueryBuilder('p')
            ->where('p.enabled = true')
            ->andWhere('p.internalKey = :k')
            ->setParameter('k', $internalKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<string> */
    public function getEnabledFormSlugs(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.formSlug')
            ->where('p.enabled = true')
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
