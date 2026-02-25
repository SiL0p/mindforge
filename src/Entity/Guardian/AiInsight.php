<?php

declare(strict_types=1);

namespace App\Entity\Guardian;

use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use App\Repository\Guardian\AiInsightRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiInsightRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'guardian_ai_insight')]
class AiInsight
{
    public const TYPE_RECOMMENDED_DURATION = 'recommended_duration';
    public const TYPE_FOCUS_TIPS = 'focus_tips';
    public const TYPE_DAILY_PLAN = 'daily_plan';
    public const TYPE_WEEKLY_REVIEW = 'weekly_review';

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

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type = self::TYPE_RECOMMENDED_DURATION;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'rule'])]
    private string $source = 'rule';

    #[ORM\Column(type: Types::TEXT)]
    private string $payload = '{}';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $helpfulVotes = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $unhelpfulVotes = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getHelpfulVotes(): int
    {
        return $this->helpfulVotes;
    }

    public function incrementHelpfulVotes(): self
    {
        $this->helpfulVotes++;

        return $this;
    }

    public function getUnhelpfulVotes(): int
    {
        return $this->unhelpfulVotes;
    }

    public function incrementUnhelpfulVotes(): self
    {
        $this->unhelpfulVotes++;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
