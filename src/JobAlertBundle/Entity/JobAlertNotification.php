<?php

namespace App\JobAlertBundle\Entity;

use App\Entity\Carriere\OpportuniteCarriere;
use App\JobAlertBundle\Repository\JobAlertNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobAlertNotificationRepository::class)]
#[ORM\Table(name: 'job_alert_notification')]
#[ORM\HasLifecycleCallbacks]
class JobAlertNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?JobAlertSubscription $subscription = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OpportuniteCarriere $opportunite = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\PrePersist]
    public function setSentAtValue(): void
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): ?JobAlertSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(?JobAlertSubscription $subscription): static
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getOpportunite(): ?OpportuniteCarriere
    {
        return $this->opportunite;
    }

    public function setOpportunite(?OpportuniteCarriere $opportunite): static
    {
        $this->opportunite = $opportunite;
        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }
}
