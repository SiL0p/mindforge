<?php

namespace App\Repository\Analyst;

use App\Entity\Analyst\Badge;
use App\Entity\Analyst\UserBadge;
use App\Entity\Architect\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBadge::class);
    }

    public function hasUserBadge(User $user, Badge $badge): bool
    {
        $count = $this->createQueryBuilder('ub')
            ->select('COUNT(ub.id)')
            ->where('ub.user = :user')
            ->andWhere('ub.badge = :badge')
            ->setParameter('user', $user)
            ->setParameter('badge', $badge)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.badge', 'b')
            ->addSelect('b')
            ->where('ub.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ub.earnedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
