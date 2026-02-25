<?php

namespace App\JobAlertBundle\Repository;

use App\JobAlertBundle\Entity\JobAlertSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobAlertSubscription>
 */
class JobAlertSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobAlertSubscription::class);
    }

    /**
     * @return JobAlertSubscription[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = true')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobAlertSubscription[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
