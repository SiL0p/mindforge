<?php

namespace App\Entity\Carriere;

use App\Entity\Architect\User;
use App\Repository\Carriere\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise')]
#[ORM\HasLifecycleCallbacks]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Company name is required.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Company name must be at least {{ limit }} characters long.',
        maxMessage: 'Company name cannot exceed {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(
        min: 5,
        max: 2000,
        minMessage: 'Description must be at least {{ limit }} characters long.',
        maxMessage: 'Description cannot exceed {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Industry is required.')]
    private ?string $industry = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\NotBlank(message: 'Contact email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Contact phone is required.')]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Website is required.')]
    #[Assert\Url(message: 'Please enter a valid website URL.')]
    private ?string $website = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Relationships
    #[ORM\OneToMany(targetEntity: OpportuniteCarriere::class, mappedBy: 'company')]
    private Collection $opportunites;

    // Many-to-Many: Entreprise can have many Users who manage it
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'entreprises')]
    #[ORM\JoinTable(name: 'entreprise_user')]
    private Collection $users;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->opportunites = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): static
    {
        $this->industry = $industry;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, OpportuniteCarriere>
     */
    public function getOpportunites(): Collection
    {
        return $this->opportunites;
    }

    public function addOpportunite(OpportuniteCarriere $opportunite): static
    {
        if (!$this->opportunites->contains($opportunite)) {
            $this->opportunites->add($opportunite);
            $opportunite->setCompany($this);
        }

        return $this;
    }

    public function removeOpportunite(OpportuniteCarriere $opportunite): static
    {
        if ($this->opportunites->removeElement($opportunite)) {
            if ($opportunite->getCompany() === $this) {
                $opportunite->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function hasUser(User $user): bool
    {
        return $this->users->contains($user);
    }
}
