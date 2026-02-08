<?php
// src/Entity/Subject.php
namespace App\Entity;

use App\Repository\Planner\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de la matière ne peut pas être vide.")]
    #[Assert\Length(max: 100, maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 7)]
    private ?string $color = "#6840d6";

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Exam::class)]
    private Collection $exams;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->exams = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        if (!$this->code) {
            $this->code = strtoupper(substr(uniqid(), -6));
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    
    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection { return $this->tasks; }
    
    /**
     * @return Collection<int, Exam>
     */
    public function getExams(): Collection { return $this->exams; }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}