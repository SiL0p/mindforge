<?php
// src/Repository/VirtualRoomRepository.php

namespace App\Repository\Guardian;

use App\Entity\Guardian\VirtualRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VirtualRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VirtualRoom::class);
    }

    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('vr')
            ->leftJoin('vr.creator', 'c')
            ->leftJoin('vr.subject', 's')
            ->leftJoin('vr.participants', 'p')
            ->addSelect('c', 's', 'p')
            ->orderBy('vr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveRooms(?int $subjectId = null): array
    {
        $qb = $this->createQueryBuilder('vr')
            ->leftJoin('vr.creator', 'c')
            ->leftJoin('vr.subject', 's')
            ->leftJoin('vr.participants', 'p')
            ->addSelect('c', 's', 'p')
            ->where('vr.isActive = :active')
            ->setParameter('active', true);

        if ($subjectId) {
            $qb->andWhere('s.id = :subject')
               ->setParameter('subject', $subjectId);
        }

        return $qb->orderBy('vr.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}