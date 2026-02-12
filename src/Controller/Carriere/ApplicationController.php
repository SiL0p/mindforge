<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Application;
use App\Entity\Carriere\CareerOpportunity;
use App\Form\Carriere\ApplicationType;
use App\Repository\Carriere\ApplicationRepository;
use App\Repository\Carriere\CompanyRepository;
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

    #[Route('/my-company-applications', name: 'app_carriere_application_my_company_applications')]
    #[IsGranted('ROLE_COMPANY')]
    public function myCompanyApplications(ApplicationRepository $applicationRepository): Response
    {
        $user = $this->getUser();
        $userCompanies = $user->getCompanies();

        if ($userCompanies->isEmpty()) {
            $this->addFlash('info', 'You are not assigned to any companies yet.');
            return $this->redirectToRoute('app_carriere_opportunity_index');
        }

        // Fetch applications from all user's companies
        $applications = [];
        foreach ($userCompanies as $company) {
            $companyApplications = $applicationRepository->findByCompany($company->getId());
            $applications = array_merge($applications, $companyApplications);
        }

        // Sort by applied date (most recent first)
        usort($applications, function($a, $b) {
            return $b->getAppliedAt() <=> $a->getAppliedAt();
        });

        return $this->render('carriere/application/my_company_applications.html.twig', [
            'applications' => $applications,
            'companies' => $userCompanies,
        ]);
    }

    #[Route('/company/{companyId}', name: 'app_carriere_application_company_applications', requirements: ['companyId' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function companyApplications(
        int $companyId,
        ApplicationRepository $applicationRepository,
        CompanyRepository $companyRepository
    ): Response {
        $user = $this->getUser();
        $company = $companyRepository->find($companyId);

        if (!$company) {
            throw $this->createNotFoundException('Company not found.');
        }

        // Check if user has access to this company
        if (!$company->hasUser($user)) {
            throw $this->createAccessDeniedException('You do not have access to this company.');
        }

        $applications = $applicationRepository->findByCompany($companyId);

        return $this->render('carriere/application/company_applications.html.twig', [
            'applications' => $applications,
            'companyId' => $companyId,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_application_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(Application $application): Response
    {
        $user = $this->getUser();
        $isApplicant = $application->getUser() && $application->getUser()->getId() === $user->getId();

        // Check if user is a company manager for this opportunity's company
        $isCompanyManager = false;
        $opportunity = $application->getOpportunity();
        if ($opportunity && $opportunity->getCompany()) {
            $isCompanyManager = $user->hasCompany($opportunity->getCompany());
        }

        // Allow access if user is either the applicant OR a company manager
        if (!$isApplicant && !$isCompanyManager) {
            throw $this->createAccessDeniedException('You do not have access to view this application.');
        }

        return $this->render('carriere/application/show.html.twig', [
            'application' => $application,
            'isCompanyView' => $isCompanyManager && !$isApplicant,
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
