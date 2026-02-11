<?php

namespace App\Repository\Community;

use App\Entity\Community\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 *
 * @method ChatMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatMessage[]    findAll()
 * @method ChatMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function save(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find chat messages by virtual room with pagination
     */
    public function findByVirtualRoomPaginated($roomId, $limit = 50, $offset = 0)
    {
        return $this->createQueryBuilder('cm')
            ->andWhere('cm.virtualRoom = :room_id')
            ->setParameter('room_id', $roomId)
            ->orderBy('cm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count messages in a virtual room
     */
    public function countByVirtualRoom($roomId): int
    {
        return $this->createQueryBuilder('cm')
            ->select('COUNT(cm.id)')
            ->andWhere('cm.virtualRoom = :room_id')
            ->setParameter('room_id', $roomId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
