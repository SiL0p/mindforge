<?php

namespace App\JobAlertBundle\Repository;

use App\JobAlertBundle\Entity\JobAlertNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobAlertNotification>
 */
class JobAlertNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobAlertNotification::class);
    }

    /**
     * Find all notifications for a given user (via subscription), ordered newest first.
     *
     * @return JobAlertNotification[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.subscription', 's')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnreadByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->join('n.subscription', 's')
            ->where('s.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if a notification already exists for a subscription + opportunite pair.
     */
    public function existsForPair(int $subscriptionId, int $opportuniteId): bool
    {
        $count = (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.subscription = :sub')
            ->andWhere('n.opportunite = :opp')
            ->setParameter('sub', $subscriptionId)
            ->setParameter('opp', $opportuniteId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
