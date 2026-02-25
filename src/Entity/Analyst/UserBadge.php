<?php

namespace App\Entity\Analyst;

use App\Entity\Architect\User;
use App\Repository\Analyst\UserBadgeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBadgeRepository::class)]
#[ORM\Table(name: 'user_badge')]
#[ORM\UniqueConstraint(name: 'uniq_user_badge', columns: ['user_id', 'badge_id'])]
#[ORM\HasLifecycleCallbacks]
class UserBadge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Badge::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Badge $badge = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $earnedAt = null;

    #[ORM\PrePersist]
    public function setEarnedAtValue(): void
    {
        if (!$this->earnedAt) {
            $this->earnedAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getBadge(): ?Badge
    {
        return $this->badge;
    }

    public function setBadge(?Badge $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    public function getEarnedAt(): ?\DateTimeImmutable
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(?\DateTimeImmutable $earnedAt): self
    {
        $this->earnedAt = $earnedAt;
        return $this;
    }
}
