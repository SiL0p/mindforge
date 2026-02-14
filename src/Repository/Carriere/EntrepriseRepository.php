<?php

namespace App\Repository\Carriere;

use App\Entity\Carriere\Entreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entreprise>
 */
class EntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entreprise::class);
    }

    /**
     * Find all entreprises ordered by name
     *
     * @return Entreprise[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find entreprises by industry
     *
     * @param string $industry
     * @return Entreprise[]
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
     * Search entreprises by name
     *
     * @param string $searchTerm
     * @return Entreprise[]
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
