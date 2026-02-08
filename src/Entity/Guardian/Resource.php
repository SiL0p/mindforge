<?php
// src/Entity/Resource.php

namespace App\Entity\Guardian;

use App\Repository\Guardian\ResourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'resource')]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
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

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Le type de ressource est requis.')]
    #[Assert\Choice(
        choices: ['pdf', 'summary', 'cheat_sheet', 'exercise'],
        message: 'Veuillez sélectionner un type valide.'
    )]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $downloadCount = 0;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}.'
    )]
    private int $rating = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    // RELATIONSHIP: Many Resources belong to One Subject
    #[ORM\ManyToOne(targetEntity: Subject::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: false, name: 'subject_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'Veuillez sélectionner une matière.')]
    private ?Subject $subject = null;

    // RELATIONSHIP: Many Resources uploaded by One User (Student+)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'uploadedResources')]
    #[ORM\JoinColumn(nullable: false, name: 'uploader_id', referencedColumnName: 'id')]
    private ?User $uploader = null;

    // Transient property for file upload (not persisted)
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'application/x-pdf'],
        mimeTypesMessage: 'Seuls les fichiers PDF sont acceptés.',
        maxSizeMessage: 'Le fichier est trop grand ({{ size }}). Maximum autorisé: {{ limit }}.'
    )]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un fichier PDF.', groups: ['create'])]
    private $file;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(string $filePath): self { $this->filePath = $filePath; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getDownloadCount(): int { return $this->downloadCount; }
    public function incrementDownloadCount(): self { $this->downloadCount++; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getSubject(): ?Subject { return $this->subject; }
    public function setSubject(?Subject $subject): self { $this->subject = $subject; return $this; }
    public function getUploader(): ?User { return $this->uploader; }
    public function setUploader(?User $uploader): self { $this->uploader = $uploader; return $this; }
    public function getFile() { return $this->file; }
    public function setFile($file): self { $this->file = $file; return $this; }
}