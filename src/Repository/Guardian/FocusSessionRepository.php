<?php

namespace App\Repository\Guardian;

use App\Entity\Architect\User;
use App\Entity\Guardian\FocusSession;
use App\Entity\Planner\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FocusSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FocusSession::class);
    }

    /**
     * Returns an associative array of daily total minutes for a user between the given dates.
     * Keys are Y-m-d strings, values are integers (total minutes).
     */
    public function getDailyMinutesByUser(
        User $user,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        // Initialize all days in range with 0 to keep charts continuous
        $period = new \DatePeriod(
            $from,
            new \DateInterval('P1D'),
            $to
        );

        $daily = [];
        foreach ($period as $date) {
            $daily[$date->format('Y-m-d')] = 0;
        }

        // Fetch raw sessions and aggregate in PHP to avoid DB-specific functions
        $sessions = $this->createQueryBuilder('fs')
            ->where('fs.user = :user')
            ->andWhere('fs.timestamp >= :from')
            ->andWhere('fs.timestamp < :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('fs.timestamp', 'ASC')
            ->getQuery()
            ->getResult();

        /** @var FocusSession $session */
        foreach ($sessions as $session) {
            $ts = $session->getTimestamp();
            if (!$ts instanceof \DateTimeInterface) {
                continue;
            }
            $dayKey = $ts->format('Y-m-d');
            if (!array_key_exists($dayKey, $daily)) {
                $daily[$dayKey] = 0;
            }
            $daily[$dayKey] += (int) $session->getDuration();
        }

        return $daily;
    }

    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('fs')
            ->leftJoin('fs.task', 't')
            ->addSelect('t')
            ->where('fs.user = :user')
            ->setParameter('user', $user)
            ->orderBy('fs.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalDurationByUser(User $user): int
    {
        $result = $this->createQueryBuilder('fs')
            ->select('COALESCE(SUM(fs.duration), 0)')
            ->where('fs.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getTodaySessionCountByUser(User $user): int
    {
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        $result = $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->where('fs.user = :user')
            ->andWhere('fs.timestamp >= :start')
            ->andWhere('fs.timestamp < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getWeekDurationByUser(User $user): int
    {
        $start = new \DateTimeImmutable('monday this week');
        $end = $start->modify('+7 days');

        $result = $this->createQueryBuilder('fs')
            ->select('COALESCE(SUM(fs.duration), 0)')
            ->where('fs.user = :user')
            ->andWhere('fs.timestamp >= :start')
            ->andWhere('fs.timestamp < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getPerTaskTotalsByUser(User $user, int $limit = 6): array
    {
        return $this->createQueryBuilder('fs')
            ->select('t.id AS task_id, t.title AS task_title, SUM(fs.duration) AS total_minutes')
            ->join('fs.task', 't')
            ->where('fs.user = :user')
            ->setParameter('user', $user)
            ->groupBy('t.id, t.title')
            ->orderBy('total_minutes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function hasRecentDuplicate(User $user, Task $task, int $duration, int $windowSeconds = 20): bool
    {
        $since = new \DateTimeImmutable('-'.max(1, $windowSeconds).' seconds');

        $count = $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->where('fs.user = :user')
            ->andWhere('fs.task = :task')
            ->andWhere('fs.duration = :duration')
            ->andWhere('fs.timestamp >= :since')
            ->setParameter('user', $user)
            ->setParameter('task', $task)
            ->setParameter('duration', $duration)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
