<?php
// src/Repository/AIUsageRepository.php

namespace App\Repository;

use App\Entity\AIUsage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AIUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIUsage::class);
    }

    /**
     * Gesamtkosten für einen User im aktuellen Monat
     */
    public function getCurrentMonthCost(User $user): float
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');

        $qb = $this->createQueryBuilder('u')
            ->select('SUM(u.cost) as total')
            ->where('u.user = :user')
            ->andWhere('u.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth);

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Anzahl Requests pro Operation im aktuellen Monat
     */
    public function getCurrentMonthStats(User $user): array
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');

        $qb = $this->createQueryBuilder('u')
            ->select('u.operation, COUNT(u.id) as count, SUM(u.cost) as cost')
            ->where('u.user = :user')
            ->andWhere('u.createdAt >= :start')
            ->groupBy('u.operation')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth);

        return $qb->getQuery()->getResult();
    }

    /**
     * Gesamtstatistik über alle Zeit
     */
    public function getTotalStats(User $user): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select(
                'COUNT(u.id) as total_requests',
                'SUM(u.inputTokens) as total_input_tokens',
                'SUM(u.outputTokens) as total_output_tokens',
                'SUM(u.cost) as total_cost'
            )
            ->where('u.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getSingleResult();
    }
}