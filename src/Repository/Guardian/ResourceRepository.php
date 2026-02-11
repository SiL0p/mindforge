<?php
// src/Repository/ResourceRepository.php

namespace App\Repository\Guardian;

use App\Entity\Guardian\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.uploader', 'u')
            ->leftJoin('r.subject', 's')
            ->addSelect('u', 's')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.uploader', 'u')
            ->leftJoin('r.subject', 's')
            ->addSelect('u', 's')
            ->where('r.id IS NOT NULL');

        if (!empty($filters['subject'])) {
            $qb->andWhere('s.id = :subject')
               ->setParameter('subject', $filters['subject']);
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('r.title LIKE :search OR r.description LIKE :search')
               ->setParameter('search', '%'.$filters['search'].'%');
        }

        return $qb->orderBy('r.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}