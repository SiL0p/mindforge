<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\CareerOpportunity;
use App\Form\Carriere\CareerOpportunityType;
use App\Repository\Carriere\CareerOpportunityRepository;
use App\Repository\Carriere\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/opportunity')]
class CareerOpportunityController extends AbstractController
{
    #[Route('', name: 'app_carriere_opportunity_index')]
    public function index(Request $request, CareerOpportunityRepository $opportunityRepository): Response
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

        return $this->render('carriere/opportunity/index.html.twig', [
            'opportunities' => $opportunities,
            'search' => [
                'keyword' => $keyword,
                'type' => $type,
                'location' => $location,
            ],
        ]);
    }

    #[Route('/new', name: 'app_carriere_opportunity_new')]
    #[IsGranted('ROLE_COMPANY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $userCompanies = $user->getCompanies();

        if ($userCompanies->isEmpty()) {
            $this->addFlash('error', 'You must be assigned to a company to create opportunities.');
            return $this->redirectToRoute('app_carriere_opportunity_index');
        }

        $opportunity = new CareerOpportunity();
        $form = $this->createForm(CareerOpportunityType::class, $opportunity, [
            'user_companies' => $userCompanies
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the selected company belongs to the user
            $company = $opportunity->getCompany();
            if ($company && !$user->hasCompany($company)) {
                $this->addFlash('error', 'You do not have access to the selected company.');
                return $this->redirectToRoute('app_carriere_opportunity_new');
            }

            $entityManager->persist($opportunity);
            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity posted successfully!');

            return $this->redirectToRoute('app_carriere_opportunity_show', ['id' => $opportunity->getId()]);
        }

        return $this->render('carriere/opportunity/new.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_opportunity_show', requirements: ['id' => '\d+'])]
    public function show(
        CareerOpportunity $opportunity,
        ApplicationRepository $applicationRepository
    ): Response {
        $user = $this->getUser();
        $hasApplied = false;

        if ($user) {
            $hasApplied = $applicationRepository->hasUserApplied(
                $user->getId(),
                $opportunity->getId()
            );
        }

        return $this->render('carriere/opportunity/show.html.twig', [
            'opportunity' => $opportunity,
            'hasApplied' => $hasApplied,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_carriere_opportunity_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function edit(
        Request $request,
        CareerOpportunity $opportunity,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $company = $opportunity->getCompany();

        // Check if user has access to this opportunity's company
        if (!$company || !$user->hasCompany($company)) {
            throw $this->createAccessDeniedException('You do not have access to edit this opportunity.');
        }

        $userCompanies = $user->getCompanies();
        $form = $this->createForm(CareerOpportunityType::class, $opportunity, [
            'user_companies' => $userCompanies
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the selected company still belongs to the user
            $newCompany = $opportunity->getCompany();
            if ($newCompany && !$user->hasCompany($newCompany)) {
                $this->addFlash('error', 'You do not have access to the selected company.');
                return $this->redirectToRoute('app_carriere_opportunity_edit', ['id' => $opportunity->getId()]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity updated successfully!');

            return $this->redirectToRoute('app_carriere_opportunity_show', ['id' => $opportunity->getId()]);
        }

        return $this->render('carriere/opportunity/edit.html.twig', [
            'opportunity' => $opportunity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_opportunity_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_COMPANY')]
    public function delete(
        Request $request,
        CareerOpportunity $opportunity,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $company = $opportunity->getCompany();

        // Check if user has access to this opportunity's company
        if (!$company || !$user->hasCompany($company)) {
            throw $this->createAccessDeniedException('You do not have access to delete this opportunity.');
        }

        if ($this->isCsrfTokenValid('delete'.$opportunity->getId(), $request->request->get('_token'))) {
            $entityManager->remove($opportunity);
            $entityManager->flush();

            $this->addFlash('success', 'Job opportunity deleted successfully.');
        }

        return $this->redirectToRoute('app_carriere_opportunity_index');
    }
}
