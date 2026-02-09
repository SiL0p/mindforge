<?php

namespace App\Repository\Carriere;

use App\Entity\Carriere\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * Find all applications by user email
     *
     * @param string $email
     * @return Application[]
     */
    public function findByUserEmail(string $email): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.userEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all applications for a specific opportunity
     *
     * @param int $opportunityId
     * @return Application[]
     */
    public function findByOpportunity(int $opportunityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.opportunity = :opportunityId')
            ->setParameter('opportunityId', $opportunityId)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has already applied to an opportunity
     *
     * @param string $email
     * @param int $opportunityId
     * @return bool
     */
    public function hasUserApplied(string $email, int $opportunityId): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.userEmail = :email')
            ->andWhere('a.opportunity = :opportunityId')
            ->andWhere('a.status != :withdrawn')
            ->setParameter('email', $email)
            ->setParameter('opportunityId', $opportunityId)
            ->setParameter('withdrawn', 'withdrawn')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find applications by status
     *
     * @param string $status
     * @return Application[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending applications for a user
     *
     * @param string $email
     * @return Application[]
     */
    public function findPendingByUser(string $email): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.userEmail = :email')
            ->andWhere('a.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', 'pending')
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count applications by opportunity
     *
     * @param int $opportunityId
     * @return int
     */
    public function countByOpportunity(int $opportunityId): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.opportunity = :opportunityId')
            ->setParameter('opportunityId', $opportunityId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
