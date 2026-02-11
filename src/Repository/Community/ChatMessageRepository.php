<?php

namespace App\Repository\Community;

use App\Entity\Community\ChatMessage;
use App\Entity\Guardian\VirtualRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function findByRoom(VirtualRoom $room, int $limit = 100): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.virtualRoom = :room')
            ->setParameter('room', $room)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
