<?php
// src/Entity/Guardian/Resource.php

namespace App\Entity\Guardian;

use App\Repository\Guardian\ResourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The title cannot be empty.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'The title must be at least {{ limit }} characters long.',
        maxMessage: 'The title cannot exceed {{ limit }} characters.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'The description cannot exceed {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please select a file.')]
    private ?string $filePath = null;

    // Transient field for file upload handling
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'application/x-pdf'],
        mimeTypesMessage: 'Only PDF files are allowed.',
        maxSizeMessage: 'The file is too large ({{ size }}). Maximum allowed is {{ limit }}.'
    )]
    private $file;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Please specify the resource type.')]
    #[Assert\Choice(
        choices: ['pdf', 'summary', 'cheat_sheet', 'exercise'],
        message: 'Please select a valid resource type.'
    )]
    private ?string $type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?int $downloadCount = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $rating = null;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please select a subject.')]
    private ?Subject $subject = null;

    #[ORM\ManyToOne(inversedBy: 'uploadedResources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploader = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->rating = 0;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): static
    {
        $this->file = $file;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDownloadCount(): ?int
    {
        return $this->downloadCount;
    }

    public function incrementDownloadCount(): static
    {
        $this->downloadCount++;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getUploader(): ?User
    {
        return $this->uploader;
    }

    public function setUploader(?User $uploader): static
    {
        $this->uploader = $uploader;
        return $this;
    }
}