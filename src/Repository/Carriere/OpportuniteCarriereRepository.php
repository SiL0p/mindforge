<?php

namespace App\Repository\Carriere;

use App\Entity\Carriere\OpportuniteCarriere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpportuniteCarriere>
 */
class OpportuniteCarriereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpportuniteCarriere::class);
    }

    /**
     * Find all active opportunities ordered by creation date
     *
     * @return OpportuniteCarriere[]
     */
    public function findActiveOpportunities(): array
    {
        return $this->createQueryBuilder('co')
            ->where('co.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('co.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search opportunities with multiple filters
     *
     * @param string|null $keyword Search in title and description
     * @param string|null $type Filter by opportunity type
     * @param string|null $location Filter by location
     * @return OpportuniteCarriere[]
     */
    public function searchOpportunities(?string $keyword, ?string $type, ?string $location): array
    {
        $qb = $this->createQueryBuilder('co')
            ->where('co.status = :status')
            ->setParameter('status', 'active');

        if ($keyword) {
            $qb->andWhere('co.title LIKE :keyword OR co.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($type) {
            $qb->andWhere('co.type = :type')
               ->setParameter('type', $type);
        }

        if ($location) {
            $qb->andWhere('co.location LIKE :location')
               ->setParameter('location', '%' . $location . '%');
        }

        return $qb->orderBy('co.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find opportunities by company
     *
     * @param int $companyId
     * @return OpportuniteCarriere[]
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('co')
            ->where('co.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('co.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active opportunities by company
     *
     * @param int $companyId
     * @return OpportuniteCarriere[]
     */
    public function findActiveByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('co')
            ->where('co.company = :companyId')
            ->andWhere('co.status = :status')
            ->setParameter('companyId', $companyId)
            ->setParameter('status', 'active')
            ->orderBy('co.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find opportunities by type
     *
     * @param string $type
     * @return OpportuniteCarriere[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('co')
            ->where('co.type = :type')
            ->andWhere('co.status = :status')
            ->setParameter('type', $type)
            ->setParameter('status', 'active')
            ->orderBy('co.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
