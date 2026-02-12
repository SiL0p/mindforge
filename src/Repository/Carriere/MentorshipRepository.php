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
     * Find all mentorships for a student (by email)
     * Order by startedAt DESC
     */
    public function findByStudentEmail(string $email): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.studentEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all mentorships for a mentor (by email)
     * Order by startedAt DESC
     */
    public function findByMentorEmail(string $email): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.mentorEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all mentorships where user is participant (student OR mentor)
     * Order by startedAt DESC
     */
    public function findByParticipant(string $email): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.studentEmail = :email OR m.mentorEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if mentorship already exists between student and mentor
     * (excludes cancelled status)
     */
    public function hasActiveMentorship(string $studentEmail, string $mentorEmail): bool
    {
        $count = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.studentEmail = :studentEmail')
            ->andWhere('m.mentorEmail = :mentorEmail')
            ->andWhere('m.status != :cancelled')
            ->setParameter('studentEmail', $studentEmail)
            ->setParameter('mentorEmail', $mentorEmail)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find pending mentorship requests for a mentor
     */
    public function findPendingByMentor(string $mentorEmail): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.mentorEmail = :email')
            ->andWhere('m.status = :status')
            ->setParameter('email', $mentorEmail)
            ->setParameter('status', 'pending')
            ->orderBy('m.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active mentorships for a student
     */
    public function findActiveByStudent(string $studentEmail): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.studentEmail = :email')
            ->andWhere('m.status = :status')
            ->setParameter('email', $studentEmail)
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
    public function countByParticipant(string $email): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.studentEmail = :email OR m.mentorEmail = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
