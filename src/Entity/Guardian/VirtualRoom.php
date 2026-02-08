<?php
// src/Entity/VirtualRoom.php

namespace App\Entity\Guardian;

use App\Repository\Guardian\VirtualRoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VirtualRoomRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'virtual_room')]
class VirtualRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la salle ne peut pas être vide.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-_]+$/',
        message: 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets et underscores.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 10])]
    #[Assert\Range(
        min: 2,
        max: 50,
        notInRangeMessage: 'Le nombre de participants doit être entre {{ min }} et {{ max }}.'
    )]
    private int $maxParticipants = 10;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    // RELATIONSHIP: Many Rooms created by One User (Student+)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdRooms')]
    #[ORM\JoinColumn(nullable: false, name: 'creator_id', referencedColumnName: 'id')]
    private ?User $creator = null;

    // RELATIONSHIP: Many Rooms belong to One Subject
    #[ORM\ManyToOne(targetEntity: Subject::class, inversedBy: 'virtualRooms')]
    #[ORM\JoinColumn(nullable: false, name: 'subject_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'Veuillez sélectionner une matière.')]
    private ?Subject $subject = null;

    // RELATIONSHIP: Many-to-Many Users participate in Many Rooms
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'joinedRooms')]
    #[ORM\JoinTable(
        name: 'virtual_room_participants',
        joinColumns: [new ORM\JoinColumn(name: 'virtual_room_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $participants;

    // RELATIONSHIP: One Room has Many ChatMessages
    #[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'virtualRoom', orphanRemoval: true)]
    private Collection $chatMessages;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getMaxParticipants(): int { return $this->maxParticipants; }
    public function setMaxParticipants(int $maxParticipants): self { $this->maxParticipants = $maxParticipants; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getCreator(): ?User { return $this->creator; }
    public function setCreator(?User $creator): self { $this->creator = $creator; return $this; }
    public function getSubject(): ?Subject { return $this->subject; }
    public function setSubject(?Subject $subject): self { $this->subject = $subject; return $this; }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(User $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }
        return $this;
    }

    public function removeParticipant(User $participant): self
    {
        $this->participants->removeElement($participant);
        return $this;
    }

    public function isFull(): bool
    {
        return $this->participants->count() >= $this->maxParticipants;
    }

    public function isParticipant(User $user): bool
    {
        return $this->participants->contains($user);
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getChatMessages(): Collection { return $this->chatMessages; }
}