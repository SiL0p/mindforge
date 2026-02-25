<?php

namespace App\Entity\Community;

use App\Entity\Architect\User;
use App\Entity\Guardian\VirtualRoom;
use App\Repository\Community\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'chat_message')]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message ne peut pas etre vide.')]
    #[Assert\Length(
        min: 1,
        max: 5000,
        maxMessage: 'Le message ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $content = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isEdited = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $editedAt = null;

    // RELATIONSHIP: Many Messages sent by One User
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'chatMessages')]
    #[ORM\JoinColumn(nullable: false, name: 'sender_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'expediteur est requis.')]
    private ?User $sender = null;

    // RELATIONSHIP: Many Messages in One VirtualRoom
    #[ORM\ManyToOne(targetEntity: VirtualRoom::class, inversedBy: 'chatMessages')]
    #[ORM\JoinColumn(nullable: false, name: 'virtual_room_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La salle virtuelle est requise.')]
    private ?VirtualRoom $virtualRoom = null;
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->editedAt = new \DateTimeImmutable();
        $this->isEdited = true;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isEdited(): bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(bool $isEdited): self
    {
        $this->isEdited = $isEdited;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEditedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getVirtualRoom(): ?VirtualRoom
    {
        return $this->virtualRoom;
    }

    public function setVirtualRoom(?VirtualRoom $virtualRoom): self
    {
        $this->virtualRoom = $virtualRoom;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->sender;
    }

    public function setAuthor(?User $author): self
    {
        $this->sender = $author;
        return $this;
    }
}
