<?php

namespace App\Entity\Carriere;

use App\Repository\Carriere\OpportuniteCarriereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OpportuniteCarriereRepository::class)]
#[ORM\Table(name: 'opportunite_carriere')]
#[ORM\HasLifecycleCallbacks]
class OpportuniteCarriere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Job title is required.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Job title must be at least {{ limit }} characters long.',
        maxMessage: 'Job title cannot exceed {{ limit }} characters.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Job description is required.')]
    #[Assert\Length(
        min: 50,
        max: 5000,
        minMessage: 'Description must be at least {{ limit }} characters long.',
        maxMessage: 'Description cannot exceed {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Please select an opportunity type.')]
    #[Assert\Choice(
        choices: ['internship', 'apprenticeship', 'fulltime', 'parttime', 'freelance'],
        message: 'Please select a valid job type.'
    )]
    private ?string $type = 'internship';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Location is required.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Location cannot exceed {{ limit }} characters.'
    )]
    private ?string $location = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Duration is required.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Duration cannot exceed {{ limit }} characters.'
    )]
    private ?string $duration = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'Application deadline is required.')]
    private ?\DateTimeImmutable $deadline = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: ['active', 'closed', 'filled'],
        message: 'Invalid status.'
    )]
    private ?string $status = 'active';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Relationships
    #[ORM\ManyToOne(inversedBy: 'opportunites')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Entreprise $company = null;

    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'opportunity', cascade: ['remove'])]
    private Collection $demandes;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
    }

    // Helper methods

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDeadlinePassed(): bool
    {
        return $this->deadline && $this->deadline < new \DateTimeImmutable();
    }

    public function getApplicationCount(): int
    {
        return $this->demandes->count();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getDeadline(): ?\DateTimeImmutable
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeImmutable $deadline): static
    {
        $this->deadline = $deadline;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompany(): ?Entreprise
    {
        return $this->company;
    }

    public function setCompany(?Entreprise $company): static
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setOpportunity($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            if ($demande->getOpportunity() === $this) {
                $demande->setOpportunity(null);
            }
        }

        return $this;
    }
}
