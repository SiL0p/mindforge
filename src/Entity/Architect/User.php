<?php

namespace App\Entity\Architect;

use App\Repository\UserRepository;
use App\Entity\Community\ChatMessage;
use App\Entity\Community\Claim;
use App\Entity\Community\SharedTask;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Guardian\Resource;
use App\Entity\Carriere\Demande;
use App\Entity\Carriere\Entreprise;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\Architect\Profile;   // ✅ ADD THIS

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // ✅ PROFILE RELATION (NO DB CHANGE)
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Profile::class)]
    private ?Profile $profile = null;

    // RELATIONSHIPS: Community Module
    // One-to-Many: User sends many ChatMessages
    #[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $chatMessages;

    // One-to-Many: User creates many Claims
    #[ORM\OneToMany(targetEntity: Claim::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $createdClaims;

    // One-to-Many: Admin handles many Claims
    #[ORM\OneToMany(targetEntity: Claim::class, mappedBy: 'assignedTo')]
    private Collection $assignedClaims;

    // One-to-Many: User shares many Tasks (sender)
    #[ORM\OneToMany(targetEntity: SharedTask::class, mappedBy: 'sharedBy', orphanRemoval: true)]
    private Collection $sharedTasksSent;

    // One-to-Many: User receives many Tasks (recipient)
    #[ORM\OneToMany(targetEntity: SharedTask::class, mappedBy: 'sharedWith', orphanRemoval: true)]
    private Collection $sharedTasksReceived;

    // RELATIONSHIPS: Guardian Module
    // One-to-Many: User creates many VirtualRooms (only Student+)
    #[ORM\OneToMany(targetEntity: VirtualRoom::class, mappedBy: 'creator', orphanRemoval: true)]
    private Collection $createdRooms;

    // Many-to-Many: User participates in many VirtualRooms
    #[ORM\ManyToMany(targetEntity: VirtualRoom::class, mappedBy: 'participants')]
    private Collection $joinedRooms;

    // One-to-Many: User uploads many Resources (only Student+)
    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'uploader', orphanRemoval: true)]
    private Collection $uploadedResources;

    // RELATIONSHIPS: Career Module
    // One-to-Many: User submits many Demandes
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $demandes;

    // Many-to-Many: User can manage many Entreprises
    #[ORM\ManyToMany(targetEntity: Entreprise::class, mappedBy: 'users')]
    private Collection $entreprises;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
        $this->chatMessages = new ArrayCollection();
        $this->createdClaims = new ArrayCollection();
        $this->assignedClaims = new ArrayCollection();
        $this->sharedTasksSent = new ArrayCollection();
        $this->sharedTasksReceived = new ArrayCollection();
        $this->createdRooms = new ArrayCollection();
        $this->joinedRooms = new ArrayCollection();
        $this->uploadedResources = new ArrayCollection();
        $this->demandes = new ArrayCollection();
        $this->entreprises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }


    // ========================================
    // COMMUNITY MODULE GETTERS/SETTERS
    // ========================================

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ChatMessage $chatMessage): self
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages->add($chatMessage);
            $chatMessage->setSender($this);
        }
        return $this;
    }

    public function removeChatMessage(ChatMessage $chatMessage): self
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            if ($chatMessage->getSender() === $this) {
                $chatMessage->setSender(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Claim>
     */
    public function getCreatedClaims(): Collection
    {
        return $this->createdClaims;
    }

    public function addCreatedClaim(Claim $claim): self
    {
        if (!$this->createdClaims->contains($claim)) {
            $this->createdClaims->add($claim);
            $claim->setCreatedBy($this);
        }
        return $this;
    }

    public function removeCreatedClaim(Claim $claim): self
    {
        if ($this->createdClaims->removeElement($claim)) {
            if ($claim->getCreatedBy() === $this) {
                $claim->setCreatedBy(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Claim>
     */
    public function getAssignedClaims(): Collection
    {
        return $this->assignedClaims;
    }

    public function addAssignedClaim(Claim $claim): self
    {
        if (!$this->assignedClaims->contains($claim)) {
            $this->assignedClaims->add($claim);
            $claim->setAssignedTo($this);
        }
        return $this;
    }

    public function removeAssignedClaim(Claim $claim): self
    {
        if ($this->assignedClaims->removeElement($claim)) {
            if ($claim->getAssignedTo() === $this) {
                $claim->setAssignedTo(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, SharedTask>
     */
    public function getSharedTasksSent(): Collection
    {
        return $this->sharedTasksSent;
    }

    public function addSharedTaskSent(SharedTask $sharedTask): self
    {
        if (!$this->sharedTasksSent->contains($sharedTask)) {
            $this->sharedTasksSent->add($sharedTask);
            $sharedTask->setSharedBy($this);
        }
        return $this;
    }

    public function removeSharedTaskSent(SharedTask $sharedTask): self
    {
        if ($this->sharedTasksSent->removeElement($sharedTask)) {
            if ($sharedTask->getSharedBy() === $this) {
                $sharedTask->setSharedBy(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, SharedTask>
     */
    public function getSharedTasksReceived(): Collection
    {
        return $this->sharedTasksReceived;
    }

    public function addSharedTaskReceived(SharedTask $sharedTask): self
    {
        if (!$this->sharedTasksReceived->contains($sharedTask)) {
            $this->sharedTasksReceived->add($sharedTask);
            $sharedTask->setSharedWith($this);
        }
        return $this;
    }

    public function removeSharedTaskReceived(SharedTask $sharedTask): self
    {
        if ($this->sharedTasksReceived->removeElement($sharedTask)) {
            if ($sharedTask->getSharedWith() === $this) {
                $sharedTask->setSharedWith(null);
            }
        }
        return $this;
    }

    // ========================================
    // GUARDIAN MODULE GETTERS/SETTERS
    // ========================================

    /**
     * @return Collection<int, VirtualRoom>
     */
    public function getCreatedRooms(): Collection
    {
        return $this->createdRooms;
    }

    public function addCreatedRoom(VirtualRoom $virtualRoom): self
    {
        if (!$this->createdRooms->contains($virtualRoom)) {
            $this->createdRooms->add($virtualRoom);
            $virtualRoom->setCreator($this);
        }
        return $this;
    }

    public function removeCreatedRoom(VirtualRoom $virtualRoom): self
    {
        if ($this->createdRooms->removeElement($virtualRoom)) {
            if ($virtualRoom->getCreator() === $this) {
                $virtualRoom->setCreator(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, VirtualRoom>
     */
    public function getJoinedRooms(): Collection
    {
        return $this->joinedRooms;
    }

    public function addJoinedRoom(VirtualRoom $virtualRoom): self
    {
        if (!$this->joinedRooms->contains($virtualRoom)) {
            $this->joinedRooms->add($virtualRoom);
            $virtualRoom->addParticipant($this);
        }
        return $this;
    }

    public function removeJoinedRoom(VirtualRoom $virtualRoom): self
    {
        if ($this->joinedRooms->removeElement($virtualRoom)) {
            $virtualRoom->removeParticipant($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getUploadedResources(): Collection
    {
        return $this->uploadedResources;
    }

    public function addUploadedResource(Resource $resource): self
    {
        if (!$this->uploadedResources->contains($resource)) {
            $this->uploadedResources->add($resource);
            $resource->setUploader($this);
        }
        return $this;
    }

    public function removeUploadedResource(Resource $resource): self
    {
        if ($this->uploadedResources->removeElement($resource)) {
            if ($resource->getUploader() === $this) {
                $resource->setUploader(null);
            }
        }
        return $this;
    }

    // ========================================
    // CAREER MODULE GETTERS/SETTERS
    // ========================================

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): self
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setUser($this);
        }
        return $this;
    }

    public function removeDemande(Demande $demande): self
    {
        if ($this->demandes->removeElement($demande)) {
            if ($demande->getUser() === $this) {
                $demande->setUser(null);
            }
        }
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ✅ PROFILE GETTER/SETTER
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        if ($profile->getUser() !== $this) {
            $profile->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Entreprise>
     */
    public function getEntreprises(): Collection
    {
        return $this->entreprises;
    }

    public function addEntreprise(Entreprise $entreprise): self
    {
        if (!$this->entreprises->contains($entreprise)) {
            $this->entreprises->add($entreprise);
            $entreprise->addUser($this);
        }

        return $this;
    }

    public function removeEntreprise(Entreprise $entreprise): self
    {
        if ($this->entreprises->removeElement($entreprise)) {
            $entreprise->removeUser($this);
        }

        return $this;
    }

    public function hasEntreprise(Entreprise $entreprise): bool
    {
        return $this->entreprises->contains($entreprise);
    }
}