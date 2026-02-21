<?php

namespace App\Entity\Community;

use App\Entity\Architect\User;
use App\Repository\Community\ClaimRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClaimRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'claim')]
class Claim
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Le titre du ticket ne peut pas être vide.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description du ticket ne peut pas être vide.')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'open'])]
    #[Assert\Choice(
        choices: ['open', 'in_progress', 'resolved', 'closed'],
        message: 'Le statut doit être parmi les valeurs acceptées.'
    )]
    private string $status = 'open';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'medium'])]
    #[Assert\Choice(
        choices: ['low', 'medium', 'high', 'critical'],
        message: 'La priorité doit être parmi les valeurs acceptées.'
    )]
    private string $priority = 'medium';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Les notes d\'administration ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    // RELATIONSHIP: Many Claims created by One User
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdClaims')]
    #[ORM\JoinColumn(nullable: false, name: 'created_by_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le créateur du ticket est requis.')]
    private ?User $createdBy = null;

    // RELATIONSHIP: Many Claims assigned to One User (Admin/Support)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedClaims')]
    #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): self
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }
}
