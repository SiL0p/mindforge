<?php

namespace App\JobAlertBundle\Controller;

use App\JobAlertBundle\Entity\JobAlertSubscription;
use App\JobAlertBundle\Form\JobAlertSubscriptionType;
use App\JobAlertBundle\Repository\JobAlertSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/alerts')]
#[IsGranted('ROLE_USER')]
class JobAlertSubscriptionController extends AbstractController
{
    #[Route('', name: 'job_alert_subscription_index', methods: ['GET'])]
    public function index(JobAlertSubscriptionRepository $repository): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User) {
            throw $this->createAccessDeniedException();
        }

        $subscriptions = $repository->findByUser($user->getId());

        return $this->render('job_alert_bundle/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/new', name: 'job_alert_subscription_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $subscription = new JobAlertSubscription();
        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User) {
            throw $this->createAccessDeniedException();
        }

        $subscription->setUser($user);

        $form = $this->createForm(JobAlertSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subscription);
            $em->flush();

            $this->addFlash('success', 'Job alert created successfully.');
            return $this->redirectToRoute('job_alert_subscription_index');
        }

        return $this->render('job_alert_bundle/subscription/form.html.twig', [
            'form' => $form,
            'subscription' => $subscription,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'job_alert_subscription_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, JobAlertSubscriptionRepository $repository): Response
    {
        $subscription = $repository->find($id);

        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User || !$subscription || $subscription->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(JobAlertSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Job alert updated successfully.');
            return $this->redirectToRoute('job_alert_subscription_index');
        }

        return $this->render('job_alert_bundle/subscription/form.html.twig', [
            'form' => $form,
            'subscription' => $subscription,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/toggle', name: 'job_alert_subscription_toggle', methods: ['POST'])]
    public function toggle(int $id, EntityManagerInterface $em, JobAlertSubscriptionRepository $repository): Response
    {
        $subscription = $repository->find($id);

        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User || !$subscription || $subscription->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $subscription->setIsActive(!$subscription->isActive());
        $em->flush();

        $this->addFlash('success', 'Alert ' . ($subscription->isActive() ? 'activated' : 'deactivated') . '.');
        return $this->redirectToRoute('job_alert_subscription_index');
    }

    #[Route('/{id}/delete', name: 'job_alert_subscription_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, JobAlertSubscriptionRepository $repository): Response
    {
        $subscription = $repository->find($id);

        $user = $this->getUser();
        /** @var \App\Entity\Architect\User|null $user */
        if (!$user instanceof \App\Entity\Architect\User || !$subscription || $subscription->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $subscription->getId(), $request->request->get('_token'))) {
            $em->remove($subscription);
            $em->flush();
            $this->addFlash('success', 'Job alert deleted.');
        }

        return $this->redirectToRoute('job_alert_subscription_index');
    }
}
