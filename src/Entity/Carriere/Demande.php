<?php

namespace App\Entity\Carriere;

use App\Entity\Architect\User;
use App\Repository\Carriere\DemandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
#[ORM\Table(name: 'demande')]
#[ORM\HasLifecycleCallbacks]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // User relationship (using real User entity)
    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'User is required.')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 50,
        max: 3000,
        minMessage: 'Cover letter should be at least {{ limit }} characters.',
        maxMessage: 'Cover letter cannot exceed {{ limit }} characters.'
    )]
    private ?string $coverLetter = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: ['pending', 'accepted', 'rejected', 'withdrawn'],
        message: 'Invalid application status.'
    )]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $appliedAt = null;

    // Relationships
    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Career opportunity is required.')]
    private ?OpportuniteCarriere $opportunity = null;

    #[ORM\PrePersist]
    public function setAppliedAtValue(): void
    {
        $this->appliedAt = new \DateTimeImmutable();
        if ($this->status === null) {
            $this->status = 'pending';
        }
    }

    // Helper methods

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function withdraw(): static
    {
        $this->status = 'withdrawn';
        return $this;
    }

    public function accept(): static
    {
        $this->status = 'accepted';
        return $this;
    }

    public function reject(): static
    {
        $this->status = 'rejected';
        return $this;
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function getOpportunity(): ?OpportuniteCarriere
    {
        return $this->opportunity;
    }

    public function setOpportunity(?OpportuniteCarriere $opportunity): static
    {
        $this->opportunity = $opportunity;
        return $this;
    }
}
