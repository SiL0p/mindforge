<?php

namespace App\Service\Analyst;

use App\Entity\Analyst\Badge;
use App\Entity\Analyst\GamificationStats;
use App\Entity\Analyst\UserBadge;
use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use App\Repository\Analyst\BadgeRepository;
use App\Repository\Analyst\GamificationStatsRepository;
use App\Repository\Analyst\UserBadgeRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private UserBadgeRepository $userBadgeRepository,
        private GamificationStatsRepository $statsRepository,
        private XpEngineService $xpEngineService,
    ) {
    }

    public function processCompletedTask(Task $task, User $user): array
    {
        $this->ensureDefaultBadges();

        $stats = $this->getOrCreateStats($user);
        $previousLevel = $stats->getCurrentLevel();
        $xpGained = $this->xpEngineService->calculateForTask($task);
        $focusMinutes = $task->getActualMinutes() ?? $task->getEstimatedMinutes() ?? 0;

        $stats
            ->addXp($xpGained)
            ->setTasksCompleted($stats->getTasksCompleted() + 1)
            ->setTotalFocusTime($stats->getTotalFocusTime() + max(0, (int) $focusMinutes))
            ->setLastActivityDate(new \DateTimeImmutable('today'));

        $unlocked = $this->unlockEligibleBadges($user, $stats);

        $this->entityManager->flush();

        $newLevel = $stats->getCurrentLevel();
        $levelUp = $newLevel > $previousLevel;

        // Choose a celebratory GIF depending on what happened
        $gifUrl = null;
        if (!empty($unlocked)) {
            $gifUrl = 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif';
        } elseif ($levelUp) {
            $gifUrl = 'https://media.giphy.com/media/26ufdipQqU2lhNA4g/giphy.gif';
        } elseif ($xpGained >= 50) {
            $gifUrl = 'https://media.giphy.com/media/3o7aD2saalBwwftBIY/giphy.gif';
        }

        return [
            'xp_gained' => $xpGained,
            'current_level' => $newLevel,
            'total_xp' => $stats->getTotalXp(),
            'unlocked_badges' => $unlocked,
            'level_up' => $levelUp,
            'gif_url' => $gifUrl,
        ];
    }

    public function processFocusSession(User $user, int $durationMinutes): array
    {
        $this->ensureDefaultBadges();

        $durationMinutes = max(0, (int) $durationMinutes);
        $xpGained = $durationMinutes * 2;
        $today = new \DateTimeImmutable('today');

        $stats = $this->getOrCreateStats($user);
        $previousLevel = $stats->getCurrentLevel();
        $lastActivityDate = $stats->getLastActivityDate();

        if ($lastActivityDate === null) {
            $stats->setStreakDays(1);
        } else {
            $dayDiff = (int) $lastActivityDate->diff($today)->format('%r%a');
            if ($dayDiff === 0) {
                // keep current streak
            } elseif ($dayDiff === 1) {
                $stats->setStreakDays($stats->getStreakDays() + 1);
            } else {
                $stats->setStreakDays(1);
            }
        }

        $stats
            ->addXp($xpGained)
            ->setTotalFocusTime($stats->getTotalFocusTime() + $durationMinutes)
            ->setLastActivityDate($today);

        $unlocked = $this->unlockEligibleBadges($user, $stats);

        $this->entityManager->flush();

        $newLevel = $stats->getCurrentLevel();
        $levelUp = $newLevel > $previousLevel;

        $gifUrl = null;
        if (!empty($unlocked)) {
            $gifUrl = 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif';
        } elseif ($levelUp) {
            $gifUrl = 'https://media.giphy.com/media/26ufdipQqU2lhNA4g/giphy.gif';
        } elseif ($xpGained >= 60) {
            $gifUrl = 'https://media.giphy.com/media/3o7aD2saalBwwftBIY/giphy.gif';
        }

        return [
            'xp_gained' => $xpGained,
            'current_level' => $newLevel,
            'total_xp' => $stats->getTotalXp(),
            'streak_days' => $stats->getStreakDays(),
            'unlocked_badges' => $unlocked,
            'level_up' => $levelUp,
            'gif_url' => $gifUrl,
        ];
    }

    public function getDashboardData(User $user): array
    {
        $this->ensureDefaultBadges();
        $stats = $this->getOrCreateStats($user);
        $badges = $this->userBadgeRepository->findByUser($user);

        return [
            'stats' => $stats,
            'badges' => $badges,
        ];
    }

    private function getOrCreateStats(User $user): GamificationStats
    {
        $stats = $this->statsRepository->findOneByUser($user);
        if ($stats) {
            return $stats;
        }

        $stats = new GamificationStats();
        $stats->setUser($user);
        $this->entityManager->persist($stats);

        return $stats;
    }

    private function unlockEligibleBadges(User $user, GamificationStats $stats): array
    {
        $eligible = $this->badgeRepository->findEligibleBadges(
            $stats->getTotalXp(),
            $stats->getTasksCompleted(),
            $stats->getTotalFocusTime()
        );

        $unlocked = [];

        foreach ($eligible as $badge) {
            if ($this->userBadgeRepository->hasUserBadge($user, $badge)) {
                continue;
            }

            $userBadge = new UserBadge();
            $userBadge->setUser($user);
            $userBadge->setBadge($badge);
            $this->entityManager->persist($userBadge);
            $unlocked[] = $badge->getName();
        }

        return $unlocked;
    }

    private function ensureDefaultBadges(): void
    {
        $defaults = [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first task.',
                'icon' => 'fa fa-shoe-prints',
                'criteria_type' => 'tasks_completed',
                'criteria_value' => 1,
                'rarity' => 'common',
            ],
            [
                'name' => 'Task Finisher',
                'description' => 'Complete 10 tasks.',
                'icon' => 'fa fa-check-circle',
                'criteria_type' => 'tasks_completed',
                'criteria_value' => 10,
                'rarity' => 'rare',
            ],
            [
                'name' => 'XP Rookie',
                'description' => 'Reach 500 XP.',
                'icon' => 'fa fa-star',
                'criteria_type' => 'xp',
                'criteria_value' => 500,
                'rarity' => 'common',
            ],
            [
                'name' => 'XP Master',
                'description' => 'Reach 2500 XP.',
                'icon' => 'fa fa-trophy',
                'criteria_type' => 'xp',
                'criteria_value' => 2500,
                'rarity' => 'epic',
            ],
            [
                'name' => 'Focus Initiate',
                'description' => 'Accumulate 120 minutes of focused work.',
                'icon' => 'fa fa-hourglass-half',
                'criteria_type' => 'focus_minutes',
                'criteria_value' => 120,
                'rarity' => 'common',
            ],
            [
                'name' => 'Focus Legend',
                'description' => 'Accumulate 600 minutes of focused work.',
                'icon' => 'fa fa-fire',
                'criteria_type' => 'focus_minutes',
                'criteria_value' => 600,
                'rarity' => 'legendary',
            ],
        ];

        $hasChanges = false;

        foreach ($defaults as $definition) {
            $badge = $this->badgeRepository->findOneBy(['name' => $definition['name']]);
            if (!$badge) {
                $badge = new Badge();
                $badge->setName($definition['name']);
                $this->entityManager->persist($badge);
                $hasChanges = true;
            }

            $badge
                ->setDescription($definition['description'])
                ->setIcon($definition['icon'])
                ->setCriteriaType($definition['criteria_type'])
                ->setCriteriaValue($definition['criteria_value'])
                ->setRarity($definition['rarity']);
        }

        if ($hasChanges) {
            $this->entityManager->flush();
        }
    }
}
