<?php

namespace App\Repository\Carriere;

use App\Entity\Carriere\Mentorship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MentorshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mentorship::class);
    }

    /**
     * Find all mentorships for a student (by user ID)
     */
    public function findByStudent(int $studentId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.student = :studentId')
            ->setParameter('studentId', $studentId)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all mentorships for a mentor (by user ID)
     */
    public function findByMentor(int $mentorId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.mentor = :mentorId')
            ->setParameter('mentorId', $mentorId)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all mentorships where user is participant (student OR mentor)
     */
    public function findByParticipant(int $userId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.student = :userId OR m.mentor = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if mentorship already exists between student and mentor (excludes cancelled status)
     */
    public function hasActiveMentorship(int $studentId, int $mentorId): bool
    {
        $count = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.student = :studentId')
            ->andWhere('m.mentor = :mentorId')
            ->andWhere('m.status != :cancelled')
            ->setParameter('studentId', $studentId)
            ->setParameter('mentorId', $mentorId)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find pending mentorship requests for a mentor
     */
    public function findPendingByMentor(int $mentorId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.mentor = :mentorId')
            ->andWhere('m.status = :status')
            ->setParameter('mentorId', $mentorId)
            ->setParameter('status', 'pending')
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active mentorships for a student
     */
    public function findActiveByStudent(int $studentId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.student = :studentId')
            ->andWhere('m.status = :status')
            ->setParameter('studentId', $studentId)
            ->setParameter('status', 'active')
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find mentorships by company
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find mentorships by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count mentorships for a user (as student or mentor)
     */
    public function countByParticipant(int $userId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.student = :userId OR m.mentor = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
