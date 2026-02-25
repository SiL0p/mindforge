<?php

namespace App\JobAlertBundle\Controller;

use App\JobAlertBundle\Repository\JobAlertNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/alerts/notifications')]
#[IsGranted('ROLE_USER')]
class JobAlertNotificationController extends AbstractController
{
    #[Route('', name: 'job_alert_notification_index', methods: ['GET'])]
    public function index(JobAlertNotificationRepository $repository): Response
    {

        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $repository->findByUser($user->getId());

        return $this->render('job_alert_bundle/notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/read', name: 'job_alert_notification_read', methods: ['POST'])]
    public function markRead(int $id, EntityManagerInterface $em, JobAlertNotificationRepository $repository): Response
    {
        $notification = $repository->find($id);

        if (!$notification || $notification->getSubscription()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->redirectToRoute('job_alert_notification_index');
    }

    #[Route('/read-all', name: 'job_alert_notification_read_all', methods: ['POST'])]
    public function markAllRead(EntityManagerInterface $em, JobAlertNotificationRepository $repository): Response
    {

        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $repository->findByUser($user->getId());

        foreach ($notifications as $notification) {
            if (!$notification->isRead()) {
                $notification->setIsRead(true);
            }
        }

        $em->flush();

        $this->addFlash('success', 'All notifications marked as read.');
        return $this->redirectToRoute('job_alert_notification_index');
    }
}
