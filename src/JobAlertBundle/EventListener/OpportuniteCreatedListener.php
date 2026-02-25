<?php

namespace App\JobAlertBundle\EventListener;

use App\Entity\Carriere\OpportuniteCarriere;
use App\JobAlertBundle\Service\AlertMatchingService;
use Doctrine\ORM\Event\PostPersistEventArgs;

class OpportuniteCreatedListener
{
    public function __construct(
        private readonly AlertMatchingService $alertMatchingService,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof OpportuniteCarriere) {
            return;
        }

        $this->alertMatchingService->matchAndNotify($entity);
    }
}
