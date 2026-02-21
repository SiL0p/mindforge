<?php

namespace App\Repository\Analyst;

use App\Entity\Analyst\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function findEligibleBadges(int $xp, int $tasksCompleted, int $focusMinutes): array
    {
        return $this->createQueryBuilder('b')
            ->where('(b.criteriaType = :xpType AND b.criteriaValue <= :xp)')
            ->orWhere('(b.criteriaType = :tasksType AND b.criteriaValue <= :tasks)')
            ->orWhere('(b.criteriaType = :focusType AND b.criteriaValue <= :focus)')
            ->setParameter('xpType', 'xp')
            ->setParameter('tasksType', 'tasks_completed')
            ->setParameter('focusType', 'focus_minutes')
            ->setParameter('xp', $xp)
            ->setParameter('tasks', $tasksCompleted)
            ->setParameter('focus', $focusMinutes)
            ->orderBy('b.criteriaValue', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
