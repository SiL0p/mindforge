<?php

namespace App\Repository\Carriere;

use App\Entity\Carriere\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Find all companies ordered by name
     *
     * @return Company[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find companies by industry
     *
     * @param string $industry
     * @return Company[]
     */
    public function findByIndustry(string $industry): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.industry = :industry')
            ->setParameter('industry', $industry)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search companies by name
     *
     * @param string $searchTerm
     * @return Company[]
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
