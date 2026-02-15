<?php
// src/Entity/Planner/Subject.php
namespace App\Entity\Planner;

use App\Repository\Planner\SubjectRepository;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Guardian\Resource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Planner\Task;
use App\Entity\Planner\Exam;

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

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Exam::class)]
    private Collection $exams;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: VirtualRoom::class)]
    private Collection $virtualRooms;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Resource::class)]
    private Collection $resources;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->exams = new ArrayCollection();
        $this->virtualRooms = new ArrayCollection();
        $this->resources = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    
    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection { return $this->tasks; }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setSubject($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getSubject() === $this) {
                $task->setSubject(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Exam>
     */
    public function getExams(): Collection { return $this->exams; }

    public function addExam(Exam $exam): static
    {
        if (!$this->exams->contains($exam)) {
            $this->exams->add($exam);
            $exam->setSubject($this);
        }

        return $this;
    }

    public function removeExam(Exam $exam): static
    {
        if ($this->exams->removeElement($exam)) {
            if ($exam->getSubject() === $this) {
                $exam->setSubject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VirtualRoom>
     */
    public function getVirtualRooms(): Collection { return $this->virtualRooms; }

    public function addVirtualRoom(VirtualRoom $virtualRoom): static
    {
        if (!$this->virtualRooms->contains($virtualRoom)) {
            $this->virtualRooms->add($virtualRoom);
            $virtualRoom->setSubject($this);
        }

        return $this;
    }

    public function removeVirtualRoom(VirtualRoom $virtualRoom): static
    {
        if ($this->virtualRooms->removeElement($virtualRoom)) {
            if ($virtualRoom->getSubject() === $this) {
                $virtualRoom->setSubject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection { return $this->resources; }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setSubject($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getSubject() === $this) {
                $resource->setSubject(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}