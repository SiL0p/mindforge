<?php

namespace App\Entity\Guardian;

use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use App\Repository\Guardian\FocusSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FocusSessionRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'focus_session')]
class FocusSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(nullable: true, name: 'task_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Task $task = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(
        min: 1,
        max: 240,
        notInRangeMessage: 'Duration must be between {{ min }} and {{ max }} minutes.'
    )]
    private int $duration = 25;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $timestamp = null;

    #[ORM\Column(name: 'ended_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(name: 'session_type', type: Types::STRING, length: 50, options: ['default' => 'pomodoro'])]
    private string $sessionType = 'pomodoro';

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->timestamp) {
            $this->timestamp = new \DateTimeImmutable();
        }
    }

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

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): self
    {
        $this->endedAt = $endedAt;
        return $this;
    }

    public function getSessionType(): string
    {
        return $this->sessionType;
    }

    public function setSessionType(string $sessionType): self
    {
        $this->sessionType = $sessionType;
        return $this;
    }
}
