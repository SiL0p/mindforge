<?php

declare(strict_types=1);

namespace App\Repository\Guardian;

use App\Entity\Guardian\AiInsight;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class AiInsightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiInsight::class);
    }

    public function createAdminFilteredQuery(?string $type = null, ?string $source = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ai')
            ->leftJoin('ai.user', 'u')
            ->leftJoin('ai.task', 't')
            ->addSelect('u', 't')
            ->orderBy('ai.createdAt', 'DESC');

        $this->applyAdminFilters($qb, $type, $source);

        return $qb;
    }

    public function getSourceDistribution(?string $type = null, ?string $source = null): array
    {
        $qb = $this->createQueryBuilder('ai')
            ->select('ai.source AS source, COUNT(ai.id) AS total')
            ->groupBy('ai.source')
            ->orderBy('total', 'DESC');

        $this->applyAdminFilters($qb, $type, $source);

        $rows = $qb->getQuery()->getArrayResult();
        $distribution = [];

        foreach ($rows as $row) {
            $distribution[(string) $row['source']] = (int) $row['total'];
        }

        return $distribution;
    }

    public function getTypeDistribution(?string $type = null, ?string $source = null): array
    {
        $qb = $this->createQueryBuilder('ai')
            ->select('ai.type AS type, COUNT(ai.id) AS total')
            ->groupBy('ai.type')
            ->orderBy('total', 'DESC');

        $this->applyAdminFilters($qb, $type, $source);

        $rows = $qb->getQuery()->getArrayResult();
        $distribution = [];

        foreach ($rows as $row) {
            $distribution[(string) $row['type']] = (int) $row['total'];
        }

        return $distribution;
    }

    private function applyAdminFilters(QueryBuilder $qb, ?string $type, ?string $source): void
    {
        if ($type !== null && $type !== '') {
            $qb->andWhere('ai.type = :type')
                ->setParameter('type', $type);
        }

        if ($source !== null && $source !== '') {
            $qb->andWhere('ai.source = :source')
                ->setParameter('source', $source);
        }
    }
}
