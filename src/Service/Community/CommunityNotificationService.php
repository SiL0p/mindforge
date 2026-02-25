<?php

namespace App\Service\Community;

use App\Entity\Community\SharedTask;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Psr\Log\LoggerInterface;

class CommunityNotificationService
{
    private string $mailFrom;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        string $mailerFrom = 'noreply@mindforge.com'
    ) {
        $this->mailFrom = $mailerFrom;
    }

    /**
     * Prépare la tâche pour éviter l'erreur de sérialisation
     */
    private function prepareTaskForEmail(SharedTask $task): SharedTask
    {
        // Supprimer temporairement le fichier uploadé pour éviter l'erreur de sérialisation
        $task->setAttachmentFile(null);
        return $task;
    }

    /**
     * Notifie le destinataire quand il reçoit un défi
     */
    public function notifyChallengeReceived(SharedTask $task): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->mailFrom)
                ->to($task->getSharedWith()->getEmail())
                ->subject("Vous avez reçu un défi : {$task->getTitle()}")
                ->htmlTemplate('emails/challenge_received.html.twig')
                ->context([
                    'task' => $this->prepareTaskForEmail($task),
                    'from_user' => $task->getSharedBy(),
                    'to_user' => $task->getSharedWith(),
                ]);

            $this->mailer->send($email);
            $this->logger->info("Email de défi envoyé à {$task->getSharedWith()->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi du défi: " . $e->getMessage());
        }
    }

    /**
     * Notifie l'expéditeur quand le défi est accepté
     */
    public function notifyChallengeAccepted(SharedTask $task): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->mailFrom)
                ->to($task->getSharedBy()->getEmail())
                ->subject("✅ Défi accepté : {$task->getTitle()}")
                ->htmlTemplate('emails/challenge_accepted.html.twig')
                ->context([
                    'task' => $this->prepareTaskForEmail($task),
                    'accepted_by' => $task->getSharedWith(),
                ]);

            $this->mailer->send($email);
            $this->logger->info("Email d'acceptation envoyé à {$task->getSharedBy()->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi d'acceptation: " . $e->getMessage());
        }
    }

    /**
     * Notifie l'expéditeur quand le défi est rejeté
     */
    public function notifyChallengeRejected(SharedTask $task): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->mailFrom)
                ->to($task->getSharedBy()->getEmail())
                ->subject("❌ Défi rejeté : {$task->getTitle()}")
                ->htmlTemplate('emails/challenge_rejected.html.twig')
                ->context([
                    'task' => $this->prepareTaskForEmail($task),
                    'rejected_by' => $task->getSharedWith(),
                ]);

            $this->mailer->send($email);
            $this->logger->info("Email de rejet envoyé à {$task->getSharedBy()->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de rejet: " . $e->getMessage());
        }
    }

    /**
     * Notifie quand le défi est complété
     */
    public function notifyChallengeCompleted(SharedTask $task): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from($this->mailFrom)
                ->to($task->getSharedBy()->getEmail())
                ->subject("🏆 Défi complété : {$task->getTitle()}")
                ->htmlTemplate('emails/challenge_completed.html.twig')
                ->context([
                    'task' => $this->prepareTaskForEmail($task),
                    'completed_by' => $task->getSharedWith(),
                ]);

            $this->mailer->send($email);
            $this->logger->info("Email de completion envoyé à {$task->getSharedBy()->getEmail()}");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de completion: " . $e->getMessage());
        }
    }

    /**
     * Envoie un rappel si pas de réponse après 3 jours
     */
    public function sendReminderAfter3Days(SharedTask $task): void
    {
        try {
            // Vérifier que le défi est toujours en attente et créé il y a plus de 3 jours
            if ($task->getStatus() !== 'pending') {
                return;
            }

            $now = new \DateTimeImmutable();
            $createdAt = $task->getCreatedAt();
            $interval = $now->diff($createdAt);

            if ($interval->days >= 3) {
                $email = (new TemplatedEmail())
                    ->from($this->mailFrom)
                    ->to($task->getSharedWith()->getEmail())
                    ->subject("⏰ Rappel : Répondez au défi '{$task->getTitle()}'")
                    ->htmlTemplate('emails/challenge_reminder.html.twig')
                    ->context([
                        'task' => $this->prepareTaskForEmail($task),
                        'from_user' => $task->getSharedBy(),
                    ]);

                $this->mailer->send($email);
                $this->logger->info("Email de rappel envoyé à {$task->getSharedWith()->getEmail()}");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi du rappel: " . $e->getMessage());
        }
    }
}
