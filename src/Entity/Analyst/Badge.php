<?php

namespace App\Entity\Analyst;

use App\Repository\Analyst\BadgeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
#[ORM\Table(name: 'badge')]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 50)]
    private string $criteriaType;

    #[ORM\Column]
    private int $criteriaValue;

    #[ORM\Column(length: 20)]
    private string $rarity = 'common';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getCriteriaType(): string
    {
        return $this->criteriaType;
    }

    public function setCriteriaType(string $criteriaType): self
    {
        $this->criteriaType = $criteriaType;
        return $this;
    }

    public function getCriteriaValue(): int
    {
        return $this->criteriaValue;
    }

    public function setCriteriaValue(int $criteriaValue): self
    {
        $this->criteriaValue = $criteriaValue;
        return $this;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): self
    {
        $this->rarity = $rarity;
        return $this;
    }
}
