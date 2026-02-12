<?php
// src/Entity/Exam.php
namespace App\Entity\Planner;


use App\Repository\Planner\ExamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'exams')]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le titre de l'examen ne peut pas être vide.")]
    #[Assert\Length(max: 150, maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La date de l'examen est obligatoire.")]
    private ?\DateTimeImmutable $examDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $importance = 5;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'exams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'exams')]
    private ?Subject $subject = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getExamDate(): ?\DateTimeImmutable { return $this->examDate; }
    public function setExamDate(\DateTimeImmutable $examDate): static { $this->examDate = $examDate; return $this; }
    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(?int $durationMinutes): static { $this->durationMinutes = $durationMinutes; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }
    public function getImportance(): ?int { return $this->importance; }
    public function setImportance(int $importance): static { $this->importance = $importance; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): static { $this->owner = $owner; return $this; }
    public function getSubject(): ?Subject { return $this->subject; }
    public function setSubject(?Subject $subject): static { $this->subject = $subject; return $this; }
}