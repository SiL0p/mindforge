<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Entreprise;
use App\Form\Carriere\EntrepriseType;
use App\Repository\Carriere\EntrepriseRepository;
use App\Repository\Carriere\OpportuniteCarriereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/entreprise')]
class EntrepriseController extends AbstractController
{
    #[Route('', name: 'app_carriere_entreprise_index')]
    public function index(EntrepriseRepository $entrepriseRepository): Response
    {
        $entreprises = $entrepriseRepository->findAllOrderedByName();

        return $this->render('carriere/entreprise/index.html.twig', [
            'companies' => $entreprises,
        ]);
    }

    #[Route('/new', name: 'app_carriere_entreprise_new')]
    #[IsGranted('ROLE_COMPANY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entreprise);
            $entityManager->flush();

            $this->addFlash('success', 'Company profile created successfully!');

            return $this->redirectToRoute('app_carriere_entreprise_show', ['id' => $entreprise->getId()]);
        }

        return $this->render('carriere/entreprise/new.html.twig', [
            'company' => $entreprise,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_entreprise_show', requirements: ['id' => '\d+'])]
    public function show(Entreprise $entreprise, OpportuniteCarriereRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->findActiveByCompany($entreprise->getId());

        return $this->render('carriere/entreprise/show.html.twig', [
            'company' => $entreprise,
            'opportunities' => $opportunities,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_carriere_entreprise_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function edit(Request $request, Entreprise $entreprise, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Company profile updated successfully!');

            return $this->redirectToRoute('app_carriere_entreprise_show', ['id' => $entreprise->getId()]);
        }

        return $this->render('carriere/entreprise/edit.html.twig', [
            'company' => $entreprise,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_entreprise_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_COMPANY')]
    public function delete(Request $request, Entreprise $entreprise, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entreprise->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entreprise);
            $entityManager->flush();

            $this->addFlash('success', 'Company deleted successfully.');
        }

        return $this->redirectToRoute('app_carriere_entreprise_index');
    }
}
