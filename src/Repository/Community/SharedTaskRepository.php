<?php

namespace App\Repository\Community;

use App\Entity\Community\SharedTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SharedTask>
 *
 * @method SharedTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method SharedTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method SharedTask[]    findAll()
 * @method SharedTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SharedTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedTask::class);
    }

    public function save(SharedTask $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SharedTask $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find tasks shared with a specific user
     */
    public function findBySharedWith($userId, $status = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->andWhere('st.sharedWith = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('st.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('st.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find tasks shared by a specific user
     */
    public function findBySharedBy($userId, $status = null)
    {
        $qb = $this->createQueryBuilder('st')
            ->andWhere('st.sharedBy = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('st.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('st.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
