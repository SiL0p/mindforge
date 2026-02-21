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

    public function getSubjectStatsForDate(User $user, \App\Entity\Planner\Subject $subject, ?\DateTimeImmutable $date): array
    {
        if (!$date) {
            return ['count' => 0, 'minutes' => 0];
        }

        $start = $date->setTime(0, 0, 0);
        $end = $start->modify('+1 day');

        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) as taskCount, SUM(t.estimatedMinutes) as totalMinutes')
            ->where('t.owner = :user')
            ->andWhere('t.subject = :subject')
            ->andWhere('t.dueDate >= :start')
            ->andWhere('t.dueDate < :end')
            ->setParameter('user', $user)
            ->setParameter('subject', $subject)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        $result = $qb->getQuery()->getSingleResult();
        
        return [
            'count' => (int) ($result['taskCount'] ?? 0),
            'minutes' => (int) ($result['totalMinutes'] ?? 0),
        ];
    }
}