<?php
// src/Entity/Planner/Task.php
namespace App\Entity\Planner;

use App\Entity\Architect\User;
use App\Repository\Planner\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'tasks')]
class Task
{
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_HIGH = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le titre de la tâche ne peut pas être vide.")]
    #[Assert\Length(
        min: 3, 
        max: 150, 
        minMessage: "Le titre doit faire au moins {{ limit }} caractères.", 
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_DONE],
        message: "Statut invalide."
    )]
    private ?string $status = self::STATUS_TODO;

    #[ORM\Column]
    #[Assert\Range(
        min: self::PRIORITY_LOW,
        max: self::PRIORITY_HIGH,
        notInRangeMessage: "La priorite doit etre entre {{ min }} et {{ max }}."
    )]
    private ?int $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 480, notInRangeMessage: "L'estimation doit être entre {{ min }} et {{ max }} minutes.")]
    private ?int $estimatedMinutes = null;

    #[ORM\Column(nullable: true)]
    private ?int $actualMinutes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Subject $subject = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }

        if ($this->status === self::STATUS_DONE && !$this->completedAt) {
            $this->completedAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if ($this->status === self::STATUS_DONE) {
            if (!$this->completedAt) {
                $this->completedAt = new \DateTimeImmutable();
            }
        } elseif ($this->completedAt) {
            $this->completedAt = null;
        }
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static
    {
        $this->status = $status;

        if ($status === self::STATUS_DONE) {
            if (!$this->completedAt) {
                $this->completedAt = new \DateTimeImmutable();
            }
        } else {
            $this->completedAt = null;
        }

        return $this;
    }
    public function getPriority(): ?int { return $this->priority; }
    public function setPriority(int $priority): static { $this->priority = $priority; return $this; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function setDueDate(?\DateTimeImmutable $dueDate): static { $this->dueDate = $dueDate; return $this; }
    public function getEstimatedMinutes(): ?int { return $this->estimatedMinutes; }
    public function setEstimatedMinutes(?int $estimatedMinutes): static { $this->estimatedMinutes = $estimatedMinutes; return $this; }
    public function getActualMinutes(): ?int { return $this->actualMinutes; }
    public function setActualMinutes(?int $actualMinutes): static { $this->actualMinutes = $actualMinutes; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): static { $this->completedAt = $completedAt; return $this; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): static { $this->owner = $owner; return $this; }
    public function getSubject(): ?Subject { return $this->subject; }
    public function setSubject(?Subject $subject): static { $this->subject = $subject; return $this; }
}