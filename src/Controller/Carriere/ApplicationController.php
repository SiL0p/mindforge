<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Application;
use App\Entity\Carriere\CareerOpportunity;
use App\Form\Carriere\ApplicationType;
use App\Repository\Carriere\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/application')]
class ApplicationController extends AbstractController
{
    #[Route('/apply/{id}', name: 'app_carriere_application_apply', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function apply(
        Request $request,
        CareerOpportunity $opportunity,
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Check if already applied
        if ($applicationRepository->hasUserApplied($user->getId(), $opportunity->getId())) {
            $this->addFlash('warning', 'You have already applied to this opportunity.');
            return $this->redirectToRoute('app_carriere_opportunity_show', ['id' => $opportunity->getId()]);
        }

        // Check if opportunity is still active
        if (!$opportunity->isActive()) {
            $this->addFlash('error', 'This opportunity is no longer active.');
            return $this->redirectToRoute('app_carriere_opportunity_show', ['id' => $opportunity->getId()]);
        }

        // Check if deadline has passed
        if ($opportunity->isDeadlinePassed()) {
            $this->addFlash('error', 'The application deadline for this opportunity has passed.');
            return $this->redirectToRoute('app_carriere_opportunity_show', ['id' => $opportunity->getId()]);
        }

        $application = new Application();
        $application->setUser($user);
        $application->setOpportunity($opportunity);

        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($application);
            $entityManager->flush();

            $this->addFlash('success', 'Your application has been submitted successfully!');

            return $this->redirectToRoute('app_carriere_application_my_applications');
        }

        return $this->render('carriere/application/apply.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/my-applications', name: 'app_carriere_application_my_applications')]
    #[IsGranted('ROLE_USER')]
    public function myApplications(ApplicationRepository $applicationRepository): Response
    {
        $user = $this->getUser();
        $applications = $applicationRepository->findByUser($user->getId());

        return $this->render('carriere/application/my_applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_application_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(Application $application): Response
    {
        // Check if the logged-in user owns this application
        $user = $this->getUser();
        if ($application->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only view your own applications.');
        }

        return $this->render('carriere/application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{id}/withdraw', name: 'app_carriere_application_withdraw', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function withdraw(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if the logged-in user owns this application
        $user = $this->getUser();
        if ($application->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only withdraw your own applications.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('withdraw'.$application->getId(), $request->request->get('_token'))) {
            if ($application->isPending()) {
                $application->withdraw();
                $entityManager->flush();

                $this->addFlash('success', 'Application withdrawn successfully.');
            } else {
                $this->addFlash('error', 'Only pending applications can be withdrawn.');
            }
        }

        return $this->redirectToRoute('app_carriere_application_my_applications');
    }
}
