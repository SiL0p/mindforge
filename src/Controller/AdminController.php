<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/tables', name: 'admin_tables')]
    public function tables(): Response
    {
        return $this->render('admin/tables.html.twig');
    }

    #[Route('/admin/charts', name: 'admin_charts')]
    public function charts(): Response
    {
        return $this->render('admin/charts.html.twig');
    }
}
