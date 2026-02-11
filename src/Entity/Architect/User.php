<?php

namespace App\Entity\Architect;

use App\Repository\UserRepository;
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

    // RELATIONSHIPS: Guardian Module
    #[ORM\OneToMany(targetEntity: 'App\Entity\Guardian\Resource', mappedBy: 'uploader')]
    private Collection $uploadedResources;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Guardian\VirtualRoom', mappedBy: 'creator')]
    private Collection $createdRooms;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\Guardian\VirtualRoom', mappedBy: 'participants')]
    private Collection $joinedRooms;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
        $this->uploadedResources = new ArrayCollection();
        $this->createdRooms = new ArrayCollection();
        $this->joinedRooms = new ArrayCollection();
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
     * @return Collection<int, App\Entity\Guardian\Resource>
     */
    public function getUploadedResources(): Collection
    {
        return $this->uploadedResources;
    }

    public function addUploadedResource($resource): self
    {
        if (!$this->uploadedResources->contains($resource)) {
            $this->uploadedResources->add($resource);
            $resource->setUploader($this);
        }
        return $this;
    }

    public function removeUploadedResource($resource): self
    {
        if ($this->uploadedResources->removeElement($resource)) {
            if ($resource->getUploader() === $this) {
                $resource->setUploader(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, App\Entity\Guardian\VirtualRoom>
     */
    public function getCreatedRooms(): Collection
    {
        return $this->createdRooms;
    }

    public function addCreatedRoom($room): self
    {
        if (!$this->createdRooms->contains($room)) {
            $this->createdRooms->add($room);
            $room->setCreator($this);
        }
        return $this;
    }

    public function removeCreatedRoom($room): self
    {
        if ($this->createdRooms->removeElement($room)) {
            if ($room->getCreator() === $this) {
                $room->setCreator(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, App\Entity\Guardian\VirtualRoom>
     */
    public function getJoinedRooms(): Collection
    {
        return $this->joinedRooms;
    }

    public function addJoinedRoom($room): self
    {
        if (!$this->joinedRooms->contains($room)) {
            $this->joinedRooms->add($room);
            $room->addParticipant($this);
        }
        return $this;
    }

    public function removeJoinedRoom($room): self
    {
        if ($this->joinedRooms->removeElement($room)) {
            $room->removeParticipant($this);
        }
        return $this;
    }
}
