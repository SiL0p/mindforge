<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Company;
use App\Form\Carriere\CompanyType;
use App\Repository\Carriere\CompanyRepository;
use App\Repository\Carriere\CareerOpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/company')]
class CompanyController extends AbstractController
{
    #[Route('', name: 'app_carriere_company_index')]
    public function index(CompanyRepository $companyRepository): Response
    {
        $companies = $companyRepository->findAllOrderedByName();

        return $this->render('carriere/company/index.html.twig', [
            'companies' => $companies,
        ]);
    }

    #[Route('/new', name: 'app_carriere_company_new')]
    #[IsGranted('ROLE_COMPANY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash('success', 'Company profile created successfully!');

            return $this->redirectToRoute('app_carriere_company_show', ['id' => $company->getId()]);
        }

        return $this->render('carriere/company/new.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_company_show', requirements: ['id' => '\d+'])]
    public function show(Company $company, CareerOpportunityRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->findActiveByCompany($company->getId());

        return $this->render('carriere/company/show.html.twig', [
            'company' => $company,
            'opportunities' => $opportunities,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_carriere_company_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_COMPANY')]
    public function edit(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Company profile updated successfully!');

            return $this->redirectToRoute('app_carriere_company_show', ['id' => $company->getId()]);
        }

        return $this->render('carriere/company/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_company_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_COMPANY')]
    public function delete(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $entityManager->remove($company);
            $entityManager->flush();

            $this->addFlash('success', 'Company deleted successfully.');
        }

        return $this->redirectToRoute('app_carriere_company_index');
    }
}
