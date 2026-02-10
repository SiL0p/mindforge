<?php
// src/Controller/Guardian/Admin/GuardianAdminController.php

namespace App\Controller\Guardian\Admin;

use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Form\AdminResourceEditType;
use App\Repository\Guardian\ResourceRepository;
use App\Repository\Guardian\VirtualRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/guardian')]
#[IsGranted('ROLE_ADMIN')]
class GuardianAdminController extends AbstractController
{
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