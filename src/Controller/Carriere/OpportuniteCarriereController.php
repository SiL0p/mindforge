<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\OpportuniteCarriere;
use App\Form\Carriere\OpportuniteCarriereType;
use App\Repository\Carriere\OpportuniteCarriereRepository;
use App\Repository\Carriere\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/opportunite')]
class OpportuniteCarriereController extends AbstractController
{
    #[Route('', name: 'app_carriere_opportunite_index')]
    public function index(Request $request, OpportuniteCarriereRepository $opportunityRepository): Response
    {
        // Get search parameters from request
        $keyword = $request->query->get('keyword');
        $type = $request->query->get('type');
        $location = $request->query->get('location');

        // Search opportunities
        if ($keyword || $type || $location) {
            $opportunities = $opportunityRepository->searchOpportunities($keyword, $type, $location);
        } else {
            $opportunities = $opportunityRepository->findActiveOpportunities();
        }

        return $this->render('carriere/opportunite_carriere/index.html.twig', [
            'opportunities' => $opportunities,
            'search' => [
                'keyword' => $keyword,
                'type' => $type,
                'location' => $location,
            ],
        ]);
    }

    #[Route('/my-opportunites', name: 'app_carriere_opportunite_my_opportunites')]
    #[IsGranted('ROLE_COMPANY')]
    public function myOpportunites(OpportuniteCarriereRepository $opportunityRepository): Response
    {
        $user = $this->getUser();
        $userCompanies = $user->getEntreprises();

        if ($userCompanies->isEmpty()) {
            $this->addFlash('info', 'You are not assigned to any companies yet.');
            return $this->redirectToRoute('app_carriere_opportunite_index');
        }

        // Fetch opportunities from all user's companies
        $opportunities = [];
        foreach ($userCompanies as $company) {
            $companyOpportunities = $opportunityRepository->findBy(['company' => $company], ['createdAt' => 'DESC']);
            $opportunities = array_merge($opportunities, $companyOpportunities);
        }

        // Sort by created date (most recent first)
        usort($opportunities, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $this->render('carriere/opportunite_carriere/my_opportunities.html.twig', [
            'opportunities' => $opportunities,
            'companies' => $userCompanies,
        ]);
    }

    #[Route('/new', name: 'app_carriere_opportunite_new')]
    #[IsGranted('ROLE_COMPANY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $userCompanies = $user->getEntreprises();

        if ($userCompanies->isEmpty()) {
            $this->addFlash('error', 'You must be assigned to a company to create opportunities.');
            return $this->redirectToRoute('app_carriere_opportunite_index');
        }

        $opportunity = new OpportuniteCarriere();
        $form = $this->createForm(OpportuniteCarriereType::class, $opportunity, [
            'user_companies' => $userCompanies
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the selected company belongs to the user
            $company = $opportunity->getCompany();
            if ($company && !$user->hasEntreprise($company)) {
                $this->addFlash('error', 'You do not have access to the selected company.');
                return $this->redirectToRoute('app_carriere_opportunite_new');
            }

            $entityManager->persist($opportunity);
            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity posted successfully!');

            return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $opportunity->getId()]);
        }

        return $this->render('carriere/opportunite_carriere/new.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_opportunite_show', requirements: ['id' => '\d+'])]
    public function show(
        OpportuniteCarriere $opportunity,
        DemandeRepository $demandeRepository
    ): Response {
        $user = $this->getUser();
        $hasApplied = false;

        if ($user) {
            $hasApplied = $demandeRepository->hasUserApplied(
                $user->getId(),
                $opportunity->getId()
            );
        }

        return $this->render('carriere/opportunite_carriere/show.html.twig', [
            'opportunity' => $opportunity,
            'hasApplied' => $hasApplied,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_carriere_opportunite_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function edit(
        Request $request,
        OpportuniteCarriere $opportunity,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $company = $opportunity->getCompany();

        // Check if user has access to this opportunity's company
        if (!$company || !$user->hasEntreprise($company)) {
            throw $this->createAccessDeniedException('You do not have access to edit this opportunity.');
        }

        $userCompanies = $user->getEntreprises();
        $form = $this->createForm(OpportuniteCarriereType::class, $opportunity, [
            'user_companies' => $userCompanies
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the selected company still belongs to the user
            $newCompany = $opportunity->getCompany();
            if ($newCompany && !$user->hasEntreprise($newCompany)) {
                $this->addFlash('error', 'You do not have access to the selected company.');
                return $this->redirectToRoute('app_carriere_opportunite_edit', ['id' => $opportunity->getId()]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity updated successfully!');

            return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $opportunity->getId()]);
        }

        return $this->render('carriere/opportunite_carriere/edit.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_opportunite_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_COMPANY')]
    public function delete(
        Request $request,
        OpportuniteCarriere $opportunity,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $company = $opportunity->getCompany();

        // Check if user has access to this opportunity's company
        if (!$company || !$user->hasEntreprise($company)) {
            throw $this->createAccessDeniedException('You do not have access to delete this opportunity.');
        }

        if ($this->isCsrfTokenValid('delete'.$opportunity->getId(), $request->request->get('_token'))) {
            $entityManager->remove($opportunity);
            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity deleted successfully.');
        }

        return $this->redirectToRoute('app_carriere_opportunite_index');
    }
}
