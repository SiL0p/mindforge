<?php

namespace App\JobAlertBundle\Twig;

use App\JobAlertBundle\Repository\JobAlertNotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class JobAlertExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly JobAlertNotificationRepository $notificationRepository,
        private readonly Security $security,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User || in_array('ROLE_COMPANY', $user->getRoles())) {
            return [
                'job_alert_unread_count' => 0,
                'job_alert_recent' => [],
            ];
        }

        $all = $this->notificationRepository->findByUser($user->getId());
        $unread = array_values(array_filter($all, fn($n) => !$n->isRead()));
        $recent = array_slice($unread, 0, 5);

        return [
            'job_alert_unread_count' => count($unread),
            'job_alert_recent' => $recent,
        ];
    }
}
