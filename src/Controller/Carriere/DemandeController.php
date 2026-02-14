<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Demande;
use App\Entity\Carriere\OpportuniteCarriere;
use App\Form\Carriere\DemandeType;
use App\Repository\Carriere\DemandeRepository;
use App\Repository\Carriere\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/demande')]
class DemandeController extends AbstractController
{
    #[Route('/apply/{id}', name: 'app_carriere_demande_apply', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function apply(
        Request $request,
        OpportuniteCarriere $opportunity,
        DemandeRepository $demandeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Check if already applied
        if ($demandeRepository->hasUserApplied($user->getId(), $opportunity->getId())) {
            $this->addFlash('warning', 'You have already applied to this opportunity.');
            return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $opportunity->getId()]);
        }

        // Check if opportunity is still active
        if (!$opportunity->isActive()) {
            $this->addFlash('error', 'This opportunity is no longer active.');
            return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $opportunity->getId()]);
        }

        // Check if deadline has passed
        if ($opportunity->isDeadlinePassed()) {
            $this->addFlash('error', 'The application deadline for this opportunity has passed.');
            return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $opportunity->getId()]);
        }

        $demande = new Demande();
        $demande->setUser($user);
        $demande->setOpportunity($opportunity);

        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demande);
            $entityManager->flush();

            $this->addFlash('success', 'Your application has been submitted successfully!');

            return $this->redirectToRoute('app_carriere_demande_my_demandes');
        }

        return $this->render('carriere/demande/apply.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/my-demandes', name: 'app_carriere_demande_my_demandes')]
    #[IsGranted('ROLE_USER')]
    public function myDemandes(DemandeRepository $demandeRepository): Response
    {
        $user = $this->getUser();
        $demandes = $demandeRepository->findByUser($user->getId());

        return $this->render('carriere/demande/my_applications.html.twig', [
            'applications' => $demandes,
        ]);
    }

    #[Route('/my-company-demandes', name: 'app_carriere_demande_my_company_demandes')]
    #[IsGranted('ROLE_COMPANY')]
    public function myCompanyDemandes(DemandeRepository $demandeRepository): Response
    {
        $user = $this->getUser();
        $userCompanies = $user->getEntreprises();

        if ($userCompanies->isEmpty()) {
            $this->addFlash('info', 'You are not assigned to any companies yet.');
            return $this->redirectToRoute('app_carriere_opportunite_index');
        }

        // Fetch demandes from all user's companies
        $demandes = [];
        foreach ($userCompanies as $company) {
            $companyDemandes = $demandeRepository->findByCompany($company->getId());
            $demandes = array_merge($demandes, $companyDemandes);
        }

        // Sort by applied date (most recent first)
        usort($demandes, function($a, $b) {
            return $b->getAppliedAt() <=> $a->getAppliedAt();
        });

        return $this->render('carriere/demande/my_company_applications.html.twig', [
            'applications' => $demandes,
            'companies' => $userCompanies,
        ]);
    }

    #[Route('/company/{companyId}', name: 'app_carriere_demande_company_demandes', requirements: ['companyId' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function companyDemandes(
        int $companyId,
        DemandeRepository $demandeRepository,
        EntrepriseRepository $entrepriseRepository
    ): Response {
        $user = $this->getUser();
        $company = $entrepriseRepository->find($companyId);

        if (!$company) {
            throw $this->createNotFoundException('Company not found.');
        }

        // Check if user has access to this company
        if (!$company->hasUser($user)) {
            throw $this->createAccessDeniedException('You do not have access to this company.');
        }

        $demandes = $demandeRepository->findByCompany($companyId);

        return $this->render('carriere/demande/company_applications.html.twig', [
            'applications' => $demandes,
            'companyId' => $companyId,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_demande_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(Demande $demande): Response
    {
        $user = $this->getUser();
        $isApplicant = $demande->getUser() && $demande->getUser()->getId() === $user->getId();

        // Check if user is a company manager for this opportunity's company
        $isCompanyManager = false;
        $opportunity = $demande->getOpportunity();
        if ($opportunity && $opportunity->getCompany()) {
            $isCompanyManager = $user->hasEntreprise($opportunity->getCompany());
        }

        // Allow access if user is either the applicant OR a company manager
        if (!$isApplicant && !$isCompanyManager) {
            throw $this->createAccessDeniedException('You do not have access to view this application.');
        }

        return $this->render('carriere/demande/show.html.twig', [
            'application' => $demande,
            'isCompanyView' => $isCompanyManager && !$isApplicant,
        ]);
    }

    #[Route('/{id}/withdraw', name: 'app_carriere_demande_withdraw', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function withdraw(
        Request $request,
        Demande $demande,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if the logged-in user owns this demande
        $user = $this->getUser();
        if ($demande->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only withdraw your own applications.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('withdraw'.$demande->getId(), $request->request->get('_token'))) {
            if ($demande->isPending()) {
                $demande->withdraw();
                $entityManager->flush();

                $this->addFlash('success', 'Application withdrawn successfully.');
            } else {
                $this->addFlash('error', 'Only pending applications can be withdrawn.');
            }
        }

        return $this->redirectToRoute('app_carriere_demande_my_demandes');
    }
}
