<?php

namespace App\Entity\Carriere;

use App\Entity\Architect\User;
use App\Repository\Carriere\MentorshipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MentorshipRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Mentorship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Student relationship (using real User entity)
    #[ORM\ManyToOne(inversedBy: 'mentorshipsAsStudent')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Student is required.')]
    private ?User $student = null;

    // Mentor relationship (using real User entity)
    #[ORM\ManyToOne(inversedBy: 'mentorshipsAsMentor')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Mentor is required.')]
    private ?User $mentor = null;

    #[ORM\ManyToOne(inversedBy: 'mentorships')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 50000, maxMessage: 'Notes cannot exceed {{ limit }} characters.')]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: ['pending', 'active', 'completed', 'cancelled'],
        message: 'Invalid mentorship status.'
    )]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\PrePersist]
    public function setStartedAtValue(): void
    {
        $this->startedAt = new \DateTimeImmutable();
        if ($this->status === null) {
            $this->status = 'pending';
        }
        if ($this->notes === null) {
            $this->notes = '[]';
        }
    }

    // Getters and setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getMentor(): ?User
    {
        return $this->mentor;
    }

    public function setMentor(?User $mentor): static
    {
        $this->mentor = $mentor;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;
        return $this;
    }

    // Status check methods

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Participant check methods

    public function isStudent(User $user): bool
    {
        return $this->student && $this->student->getId() === $user->getId();
    }

    public function isMentor(User $user): bool
    {
        return $this->mentor && $this->mentor->getId() === $user->getId();
    }

    public function isParticipant(User $user): bool
    {
        return $this->isStudent($user) || $this->isMentor($user);
    }

    // Status transition methods

    public function accept(): static
    {
        if ($this->status !== 'pending') {
            throw new \LogicException('Only pending mentorships can be accepted.');
        }
        $this->status = 'active';
        return $this;
    }

    public function complete(): static
    {
        if ($this->status !== 'active') {
            throw new \LogicException('Only active mentorships can be completed.');
        }
        $this->status = 'completed';
        $this->endedAt = new \DateTimeImmutable();
        return $this;
    }

    public function cancel(): static
    {
        if (!in_array($this->status, ['pending', 'active'])) {
            throw new \LogicException('Only pending or active mentorships can be cancelled.');
        }
        $this->status = 'cancelled';
        $this->endedAt = new \DateTimeImmutable();
        return $this;
    }

    // Notes management methods

    public function addNote(User $author, string $content): static
    {
        $notesArray = $this->getNotesArray();

        $notesArray[] = [
            'author' => $author->getEmail(),
            'content' => $content,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];

        $this->notes = json_encode($notesArray);
        return $this;
    }

    public function getNotesArray(): array
    {
        if ($this->notes === null || $this->notes === '[]') {
            return [];
        }

        $decoded = json_decode($this->notes, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getLatestNote(): ?array
    {
        $notes = $this->getNotesArray();
        return !empty($notes) ? end($notes) : null;
    }
}
