<?php

namespace App\Entity\Planner;

use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Repository\Planner\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ORM\Table(name: 'subject')]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 120, unique: true)]
    #[Assert\NotBlank(message: 'Le nom de la matiere est requis.')]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le nom ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Resource::class, orphanRemoval: true)]
    private Collection $resources;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: VirtualRoom::class, orphanRemoval: true)]
    private Collection $virtualRooms;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
        $this->virtualRooms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): self
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setSubject($this);
        }
        return $this;
    }

    public function removeResource(Resource $resource): self
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getSubject() === $this) {
                $resource->setSubject(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, VirtualRoom>
     */
    public function getVirtualRooms(): Collection
    {
        return $this->virtualRooms;
    }

    public function addVirtualRoom(VirtualRoom $virtualRoom): self
    {
        if (!$this->virtualRooms->contains($virtualRoom)) {
            $this->virtualRooms->add($virtualRoom);
            $virtualRoom->setSubject($this);
        }
        return $this;
    }

    public function removeVirtualRoom(VirtualRoom $virtualRoom): self
    {
        if ($this->virtualRooms->removeElement($virtualRoom)) {
            if ($virtualRoom->getSubject() === $this) {
                $virtualRoom->setSubject(null);
            }
        }
        return $this;
    }
}
