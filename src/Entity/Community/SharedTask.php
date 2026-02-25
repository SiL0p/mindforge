<?php

namespace App\Entity\Community;

use App\Entity\Architect\User;
use App\Repository\Community\SharedTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: SharedTaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'shared_task')]
#[Vich\Uploadable]
class SharedTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Le titre du défi ne peut pas être vide.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'pending'])]
    #[Assert\Choice(
        choices: ['pending', 'accepted', 'rejected', 'completed'],
        message: 'Le statut doit être parmi les valeurs acceptées.'
    )]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'tech_skills'])]
    #[Assert\Choice(
        choices: ['tech_skills', 'soft_skills', 'physical', 'creative'],
        message: 'La catégorie doit être parmi les valeurs acceptées.'
    )]
    private string $category = 'tech_skills';

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['default' => 'medium'])]
    #[Assert\Choice(
        choices: ['easy', 'medium', 'hard'],
        message: 'La difficulté doit être: easy, medium ou hard.'
    )]
    private ?string $difficulty = 'medium';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $attachment = null;

    #[Vich\UploadableField(mapping: 'challenges', fileNameProperty: 'attachment')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        mimeTypesMessage: 'Formats acceptés: PDF, JPG, PNG, DOC, DOCX'
    )]
    private ?File $attachmentFile = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sharedTasksSent')]
    #[ORM\JoinColumn(nullable: false, name: 'shared_by_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'expéditeur du défi est requis.')]
    private ?User $sharedBy = null;

    // RELATIONSHIP: SharedTask sent to One User (Receiver)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sharedTasksReceived')]
    #[ORM\JoinColumn(nullable: false, name: 'shared_with_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le destinataire du défi est requis.')]
    private ?User $sharedWith = null;

    // Note: Once Planner module is implemented, add ManyToOne relationship to Task
    // #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'sharedTasks')]
    // #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    // private ?Task $task = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function setDescription(?string $description): self
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): self
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }

    public function getSharedBy(): ?User
    {
        return $this->sharedBy;
    }

    public function setSharedBy(?User $sharedBy): self
    {
        $this->sharedBy = $sharedBy;
        return $this;
    }

    public function getSharedWith(): ?User
    {
        return $this->sharedWith;
    }

    public function setSharedWith(?User $sharedWith): self
    {
        $this->sharedWith = $sharedWith;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): self
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;
        return $this;
    }

    public function getAttachmentFile(): ?File
    {
        return $this->attachmentFile;
    }

    public function setAttachmentFile(?File $attachmentFile): self
    {
        $this->attachmentFile = $attachmentFile;
        if ($attachmentFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
