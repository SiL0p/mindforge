<?php
// src/Repository/Planner/TaskRepository.php
namespace App\Repository\Planner;

use App\Entity\Planner\Task;
use App\Entity\Architect\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findPendingByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->andWhere('t.status != :done')
            ->setParameter('user', $user)
            ->setParameter('done', Task::STATUS_DONE)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}