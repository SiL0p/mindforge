<?php

namespace App\Repository\Analyst;

use App\Entity\Analyst\GamificationStats;
use App\Entity\Architect\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GamificationStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GamificationStats::class);
    }

    public function findOneByUser(User $user): ?GamificationStats
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * @return GamificationStats[]
     */
    public function findTopLeaderboard(int $limit = 12): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.user', 'u')->addSelect('u')
            ->orderBy('g.totalXp', 'DESC')
            ->addOrderBy('g.currentLevel', 'DESC')
            ->addOrderBy('g.tasksCompleted', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User[] $users
     * @return array<int, GamificationStats>
     */
    public function findByUsersIndexed(array $users): array
    {
        if ($users === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('g')
            ->leftJoin('g.user', 'u')->addSelect('u')
            ->where('g.user IN (:users)')
            ->setParameter('users', $users)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($rows as $row) {
            $user = $row->getUser();
            if ($user && $user->getId() !== null) {
                $indexed[(int) $user->getId()] = $row;
            }
        }

        return $indexed;
    }

    public function findLeaderboardPosition(User $user): ?int
    {
        $self = $this->findOneByUser($user);
        if (!$self) {
            return null;
        }

        $higherCount = (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.totalXp > :xp')
            ->orWhere('g.totalXp = :xp AND g.currentLevel > :level')
            ->orWhere('g.totalXp = :xp AND g.currentLevel = :level AND g.tasksCompleted > :tasks')
            ->setParameter('xp', $self->getTotalXp())
            ->setParameter('level', $self->getCurrentLevel())
            ->setParameter('tasks', $self->getTasksCompleted())
            ->getQuery()
            ->getSingleScalarResult();

        return $higherCount + 1;
    }
}
