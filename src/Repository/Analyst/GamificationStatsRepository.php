<?php

namespace App\Repository\Analyst;

use App\Entity\Analyst\GamificationStats;
use App\Entity\Architect\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GamificationStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamificationStats::class);
    }

    public function findOneByUser(User $user): ?GamificationStats
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * @return GamificationStats[]
     */
    public function findTopLeaderboard(int $limit = 12): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.user', 'u')->addSelect('u')
            ->orderBy('g.totalXp', 'DESC')
            ->addOrderBy('g.currentLevel', 'DESC')
            ->addOrderBy('g.tasksCompleted', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
