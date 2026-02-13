<?php

namespace App\Entity\Analyst;

use App\Entity\Architect\User;
use App\Repository\Analyst\GamificationStatsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GamificationStatsRepository::class)]
#[ORM\Table(name: 'gamification_stats')]
class GamificationStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalXp = 0;

    #[ORM\Column(options: ['default' => 1])]
    private int $currentLevel = 1;

    #[ORM\Column(options: ['default' => 0])]
    private int $streakDays = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastActivityDate = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalFocusTime = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $tasksCompleted = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    public function setTotalXp(int $totalXp): self
    {
        $this->totalXp = max(0, $totalXp);
        return $this;
    }

    public function addXp(int $xp): self
    {
        $this->totalXp = max(0, $this->totalXp + $xp);
        $this->currentLevel = intdiv($this->totalXp, 500) + 1;
        return $this;
    }

    public function getCurrentLevel(): int
    {
        return $this->currentLevel;
    }

    public function setCurrentLevel(int $currentLevel): self
    {
        $this->currentLevel = max(1, $currentLevel);
        return $this;
    }

    public function getStreakDays(): int
    {
        return $this->streakDays;
    }

    public function setStreakDays(int $streakDays): self
    {
        $this->streakDays = max(0, $streakDays);
        return $this;
    }

    public function getLastActivityDate(): ?\DateTimeImmutable
    {
        return $this->lastActivityDate;
    }

    public function setLastActivityDate(?\DateTimeImmutable $lastActivityDate): self
    {
        $this->lastActivityDate = $lastActivityDate;
        return $this;
    }

    public function getTotalFocusTime(): int
    {
        return $this->totalFocusTime;
    }

    public function setTotalFocusTime(int $totalFocusTime): self
    {
        $this->totalFocusTime = max(0, $totalFocusTime);
        return $this;
    }

    public function getTasksCompleted(): int
    {
        return $this->tasksCompleted;
    }

    public function setTasksCompleted(int $tasksCompleted): self
    {
        $this->tasksCompleted = max(0, $tasksCompleted);
        return $this;
    }
}
