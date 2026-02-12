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
     * Find all applications by user ID
     *
     * @param int $userId
     * @return Application[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
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
     * @param int $userId
     * @param int $opportunityId
     * @return bool
     */
    public function hasUserApplied(int $userId, int $opportunityId): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.user = :userId')
            ->andWhere('a.opportunity = :opportunityId')
            ->andWhere('a.status != :withdrawn')
            ->setParameter('userId', $userId)
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
     * @param int $userId
     * @return Application[]
     */
    public function findPendingByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->andWhere('a.status = :status')
            ->setParameter('userId', $userId)
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

    /**
     * Find all applications to opportunities belonging to a company
     *
     * @param int $companyId
     * @return Application[]
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.opportunity', 'o')
            ->where('o.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('a.appliedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
