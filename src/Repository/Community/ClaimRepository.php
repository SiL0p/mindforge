<?php

namespace App\Repository\Community;

use App\Entity\Community\Claim;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Claim>
 *
 * @method Claim|null find($id, $lockMode = null, $lockVersion = null)
 * @method Claim|null findOneBy(array $criteria, array $orderBy = null)
 * @method Claim[]    findAll()
 * @method Claim[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Claim::class);
    }

    public function save(Claim $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Claim $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find claims created by a specific user
     */
    public function findByUser($userId)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user_id')
            ->setParameter('user_id', $userId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find open claims (for admin dashboard)
     */
    public function findOpenClaims()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('statuses', ['open', 'in_progress'])
            ->orderBy('c.priority', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find claims by status and priority
     */
    public function findByStatusAndPriority($status, $priority = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createdAt', 'DESC');

        if ($priority) {
            $qb->andWhere('c.priority = :priority')
                ->setParameter('priority', $priority);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count open claims
     */
    public function countOpenClaims(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('statuses', ['open', 'in_progress'])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
