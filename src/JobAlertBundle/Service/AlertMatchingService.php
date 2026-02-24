<?php

namespace App\JobAlertBundle\Service;

use App\Entity\Carriere\OpportuniteCarriere;
use App\JobAlertBundle\Entity\JobAlertNotification;
use App\JobAlertBundle\Repository\JobAlertNotificationRepository;
use App\JobAlertBundle\Repository\JobAlertSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlertMatchingService
{
    public function __construct(
        private readonly JobAlertSubscriptionRepository $subscriptionRepository,
        private readonly JobAlertNotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function matchAndNotify(OpportuniteCarriere $opportunite): void
    {
        $subscriptions = $this->subscriptionRepository->findAllActive();

        foreach ($subscriptions as $subscription) {
            if (!$this->matches($subscription, $opportunite)) {
                continue;
            }

            // Duplicate guard
            if ($this->notificationRepository->existsForPair($subscription->getId(), $opportunite->getId())) {
                continue;
            }

            $notification = new JobAlertNotification();
            $notification->setSubscription($subscription);
            $notification->setOpportunite($opportunite);

            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    private function matches(\App\JobAlertBundle\Entity\JobAlertSubscription $subscription, OpportuniteCarriere $opportunite): bool
    {
        if ($subscription->getKeywords() !== null && $subscription->getKeywords() !== '') {
            $keyword = mb_strtolower($subscription->getKeywords());
            $title = mb_strtolower($opportunite->getTitle() ?? '');
            $description = mb_strtolower($opportunite->getDescription() ?? '');
            if (!str_contains($title, $keyword) && !str_contains($description, $keyword)) {
                return false;
            }
        }

        if ($subscription->getType() !== null && $subscription->getType() !== '') {
            if ($opportunite->getType() !== $subscription->getType()) {
                return false;
            }
        }

        if ($subscription->getLocation() !== null && $subscription->getLocation() !== '') {
            $subLocation = mb_strtolower($subscription->getLocation());
            $jobLocation = mb_strtolower($opportunite->getLocation() ?? '');
            if (!str_contains($jobLocation, $subLocation)) {
                return false;
            }
        }

        return true;
    }
}
