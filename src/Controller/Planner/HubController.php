<?php

namespace App\Controller\Planner;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planner')]
#[IsGranted('ROLE_USER')]
class HubController extends AbstractController
{
    #[Route('', name: 'app_planner_entry', methods: ['GET'])]
    public function entry(): Response
    {
        return $this->redirectToRoute('app_planner_hub');
    }

    #[Route('/hub', name: 'app_planner_hub', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('planner/hub.html.twig');
    }
}
