<?php
// src/Repository/Planner/ExamRepository.php
namespace App\Repository\Planner;

use App\Entity\Planner\Exam;
use App\Entity\Architect\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exam::class);
    }

    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.owner = :user')
            ->andWhere('e.examDate > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.examDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}