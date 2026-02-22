<?php
// src/Controller/Guardian/Admin/GuardianAdminController.php

namespace App\Controller\Guardian\Admin;

use App\Entity\Guardian\AiInsight;
use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Form\Guardian\AdminResourceEditType;
use App\Repository\Guardian\AiInsightRepository;
use App\Repository\Guardian\ResourceRepository;
use App\Repository\Guardian\VirtualRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/guardian')]
#[IsGranted('ROLE_ADMIN')]
class GuardianAdminController extends AbstractController
{
    #[Route('/ai-insights', name: 'admin_guardian_ai_insight_index', methods: ['GET'])]
    public function listAiInsights(
        Request $request,
        AiInsightRepository $repo,
        PaginatorInterface $paginator,
        ChartBuilderInterface $chartBuilder
    ): Response
    {
        $typeFilter = trim((string) $request->query->get('type', ''));
        $sourceFilter = trim((string) $request->query->get('source', ''));

        $query = $repo->createAdminFilteredQuery(
            $typeFilter !== '' ? $typeFilter : null,
            $sourceFilter !== '' ? $sourceFilter : null
        );

        $perPage = max(10, min(200, (int) $request->query->get('limit', 25)));
        $insights = $paginator->paginate(
            $query,
            max(1, (int) $request->query->get('page', 1)),
            $perPage
        );

        $sourceDistribution = $repo->getSourceDistribution(
            $typeFilter !== '' ? $typeFilter : null,
            $sourceFilter !== '' ? $sourceFilter : null
        );
        $typeDistribution = $repo->getTypeDistribution(
            $typeFilter !== '' ? $typeFilter : null,
            $sourceFilter !== '' ? $sourceFilter : null
        );

        $sourceChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $sourceChart->setData([
            'labels' => array_map(static fn (string $label): string => strtoupper($label), array_keys($sourceDistribution)),
            'datasets' => [[
                'label' => 'Insights by Source',
                'backgroundColor' => ['#22c55e', '#64748b', '#0ea5e9', '#f59e0b'],
                'data' => array_values($sourceDistribution),
            ]],
        ]);
        $sourceChart->setOptions([
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'maintainAspectRatio' => false,
        ]);

        $typeChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $typeChart->setData([
            'labels' => array_map(static fn (string $label): string => str_replace('_', ' ', $label), array_keys($typeDistribution)),
            'datasets' => [[
                'label' => 'Insights by Type',
                'backgroundColor' => '#3b82f6',
                'data' => array_values($typeDistribution),
            ]],
        ]);
        $typeChart->setOptions([
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'maintainAspectRatio' => false,
        ]);

        return $this->render('admin/guardian/ai_insight_index.html.twig', [
            'insights' => $insights,
            'source_chart' => $sourceChart,
            'type_chart' => $typeChart,
            'limit' => $perPage,
            'filters' => [
                'type' => $typeFilter,
                'source' => $sourceFilter,
            ],
            'type_labels' => [
                AiInsight::TYPE_RECOMMENDED_DURATION => 'Recommended Duration',
                AiInsight::TYPE_FOCUS_TIPS => 'Focus Tips',
                AiInsight::TYPE_DAILY_PLAN => 'Daily Plan',
                AiInsight::TYPE_WEEKLY_REVIEW => 'Weekly Review',
            ],
        ]);
    }

    // ==================== RESOURCE MANAGEMENT ====================

    #[Route('/resources', name: 'admin_resource_index', methods: ['GET'])]
    public function listResources(ResourceRepository $repo): Response
    {
        return $this->render('admin/guardian/resource_index.html.twig', [
            'resources' => $repo->findAllWithDetails(),
        ]);
    }

    #[Route('/resources/{id}/edit', name: 'admin_resource_edit', methods: ['GET', 'POST'])]
    public function editResource(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(AdminResourceEditType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ressource modifiée avec succès.');
            return $this->redirectToRoute('admin_resource_index');
        }

        return $this->render('admin/guardian/resource_edit.html.twig', [
            'form' => $form,
            'resource' => $resource,
        ]);
    }

    #[Route('/resources/{id}/delete', name: 'admin_resource_delete', methods: ['POST'])]
    public function deleteResource(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em,
        string $resourcesDirectory
    ): Response {
        if ($this->isCsrfTokenValid('delete_resource_'.$resource->getId(), $request->request->get('_token'))) {
            // Delete physical file
            $filePath = $resourcesDirectory.'/'.$resource->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $em->remove($resource);
            $em->flush();
            $this->addFlash('success', 'Ressource supprimée définitivement.');
        }

        return $this->redirectToRoute('admin_resource_index');
    }

    // ==================== VIRTUAL ROOM MANAGEMENT ====================

    #[Route('/rooms', name: 'admin_room_index', methods: ['GET'])]
    public function listRooms(VirtualRoomRepository $repo): Response
    {
        return $this->render('admin/guardian/room_index.html.twig', [
            'rooms' => $repo->findAllWithDetails(),
        ]);
    }

    #[Route('/rooms/{id}/toggle', name: 'admin_room_toggle', methods: ['POST'])]
    public function toggleRoom(
        Request $request,
        VirtualRoom $room,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('toggle_room_'.$room->getId(), $request->request->get('_token'))) {
            $room->setIsActive(!$room->isActive());
            $em->flush();
            
            $status = $room->isActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', "Salle virtuelle {$status}.");
        }

        return $this->redirectToRoute('admin_room_index');
    }

    #[Route('/rooms/{id}/delete', name: 'admin_room_delete', methods: ['POST'])]
    public function deleteRoom(
        Request $request,
        VirtualRoom $room,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete_room_'.$room->getId(), $request->request->get('_token'))) {
            $em->remove($room);
            $em->flush();
            $this->addFlash('success', 'Salle supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_room_index');
    }
}