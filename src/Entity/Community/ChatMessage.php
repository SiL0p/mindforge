<?php

namespace App\Entity\Community;

use App\Entity\Architect\User;
use App\Entity\Guardian\VirtualRoom;
use App\Repository\Community\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
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
        max: 2000,
        minMessage: 'Le message doit contenir au moins {{ limit }} caractere.',
        maxMessage: 'Le message ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: VirtualRoom::class, inversedBy: 'chatMessages')]
    #[ORM\JoinColumn(nullable: false, name: 'virtual_room_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?VirtualRoom $virtualRoom = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, name: 'author_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;
        return $this;
    }
}
