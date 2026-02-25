<?php
namespace App\Entity\Architect;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Architect\EmotionLogRepository;  // ← fixed

#[ORM\Entity(repositoryClass: EmotionLogRepository::class)]
class EmotionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $emotion;

    #[ORM\Column(type: 'float')]
    private float $confidence;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $detectedAt;

    public function __construct(string $emotion, float $confidence, $user)
    {
        $this->emotion = $emotion;
        $this->confidence = $confidence;
        $this->user = $user;
        $this->detectedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmotion(): string { return $this->emotion; }
    public function getConfidence(): float { return $this->confidence; }
    public function getDetectedAt(): \DateTimeImmutable { return $this->detectedAt; }
    public function getUser() { return $this->user; }
}
