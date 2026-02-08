<?php
// src/Controller/GuardianController.php

namespace App\Controller\Guardian;

use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Form\Guardian\ResourceType;
use App\Form\Guardian\VirtualRoomType;
use App\Repository\Guardian\ResourceRepository;
use App\Repository\Architect\SubjectRepository;
use App\Repository\Guardian\VirtualRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/guardian')]
#[IsGranted('ROLE_USER')]
class GuardianController extends AbstractController
{
    public function __construct(
        private string $resourcesDirectory,
        private SluggerInterface $slugger
    ) {}

    // ==================== RESOURCE LIBRARY (FRONT) ====================

    #[Route('/library', name: 'guardian_library', methods: ['GET'])]
    public function library(
        Request $request,
        ResourceRepository $repo,
        SubjectRepository $subjectRepo
    ): Response {
        $filters = [
            'subject' => $request->query->get('subject'),
            'type' => $request->query->get('type'),
            'search' => $request->query->get('search'),
        ];

        return $this->render('guardian/library.html.twig', [
            'resources' => $repo->findByFilters($filters),
            'subjects' => $subjectRepo->findAllActive(),
            'filters' => $filters,
            'can_upload' => $this->isGranted('ROLE_STUDENT_PLUS'),
        ]);
    }

    #[Route('/library/upload', name: 'guardian_resource_upload', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT_PLUS')]
    public function uploadResource(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $resource = new Resource();
        $form = $this->createForm(ResourceType::class, $resource, ['require_file' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $this->slugger->slug($originalName);
                $newFilename = $safeName.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move($this->resourcesDirectory, $newFilename);
                    $resource->setFilePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('guardian_resource_upload');
                }
            }

            $resource->setUploader($this->getUser());
            $em->persist($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource uploadée avec succès ! Merci pour votre contribution.');
            return $this->redirectToRoute('guardian_library');
        }

        return $this->render('guardian/resource_upload.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/library/resource/{id}/download', name: 'guardian_resource_download', methods: ['GET'])]
    public function downloadResource(
        Resource $resource,
        EntityManagerInterface $em
    ): Response {
        $filePath = $this->resourcesDirectory.'/'.$resource->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $resource->incrementDownloadCount();
        $em->flush();

        return (new BinaryFileResponse($filePath))
            ->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $resource->getTitle().'.pdf'
            );
    }

    #[Route('/library/resource/{id}/delete', name: 'guardian_resource_delete', methods: ['POST'])]
    public function deleteOwnResource(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em
    ): Response {
        // Authorization: Owner or Admin
        if (!$this->isGranted('ROLE_ADMIN') && $resource->getUploader() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres ressources.');
        }

        if ($this->isCsrfTokenValid('delete_own_resource_'.$resource->getId(), $request->request->get('_token'))) {
            $filePath = $this->resourcesDirectory.'/'.$resource->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $em->remove($resource);
            $em->flush();
            $this->addFlash('success', 'Ressource supprimée.');
        }

        return $this->redirectToRoute('guardian_library');
    }

    // ==================== VIRTUAL ROOMS (FRONT) ====================

    #[Route('/rooms', name: 'guardian_rooms', methods: ['GET'])]
    public function listRooms(
        Request $request,
        VirtualRoomRepository $repo,
        SubjectRepository $subjectRepo
    ): Response {
        $subjectId = $request->query->get('subject');
        
        return $this->render('guardian/rooms.html.twig', [
            'rooms' => $repo->findActiveRooms($subjectId),
            'subjects' => $subjectRepo->findAllActive(),
            'current_subject' => $subjectId,
            'can_create' => $this->isGranted('ROLE_STUDENT_PLUS'),
        ]);
    }

    #[Route('/rooms/create', name: 'guardian_room_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT_PLUS')]
    public function createRoom(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $room = new VirtualRoom();
        $form = $this->createForm(VirtualRoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room->setCreator($this->getUser());
            $room->addParticipant($this->getUser()); // Creator auto-joins

            $em->persist($room);
            $em->flush();

            $this->addFlash('success', 'Salle créée ! Vous avez rejoint automatiquement.');
            return $this->redirectToRoute('guardian_room_detail', ['id' => $room->getId()]);
        }

        return $this->render('guardian/room_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/rooms/{id}', name: 'guardian_room_detail', methods: ['GET'])]
    public function roomDetail(VirtualRoom $room): Response
    {
        if (!$room->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette salle est fermée.');
            return $this->redirectToRoute('guardian_rooms');
        }

        $isParticipant = $room->isParticipant($this->getUser());

        if (!$isParticipant && $room->isFull()) {
            $this->addFlash('error', 'Cette salle est complète.');
            return $this->redirectToRoute('guardian_rooms');
        }

        return $this->render('guardian/room_detail.html.twig', [
            'room' => $room,
            'is_participant' => $isParticipant,
            'messages' => $room->getChatMessages(),
        ]);
    }

    #[Route('/rooms/{id}/join', name: 'guardian_room_join', methods: ['POST'])]
    public function joinRoom(
        Request $request,
        VirtualRoom $room,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('join_room_'.$room->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('guardian_rooms');
        }

        if (!$room->isActive()) {
            $this->addFlash('error', 'Salle fermée.');
            return $this->redirectToRoute('guardian_rooms');
        }

        if ($room->isFull()) {
            $this->addFlash('error', 'Salle complète.');
            return $this->redirectToRoute('guardian_rooms');
        }

        if (!$room->isParticipant($this->getUser())) {
            $room->addParticipant($this->getUser());
            $em->flush();
            $this->addFlash('success', 'Vous avez rejoint la salle.');
        }

        return $this->redirectToRoute('guardian_room_detail', ['id' => $room->getId()]);
    }

    #[Route('/rooms/{id}/leave', name: 'guardian_room_leave', methods: ['POST'])]
    public function leaveRoom(
        Request $request,
        VirtualRoom $room,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('leave_room_'.$room->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('guardian_room_detail', ['id' => $room->getId()]);
        }

        $room->removeParticipant($this->getUser());

        // If creator leaves, close the room
        if ($room->getCreator() === $this->getUser()) {
            $room->setIsActive(false);
            $this->addFlash('info', 'Vous avez quitté. La salle est fermée car vous étiez le créateur.');
        } else {
            $this->addFlash('success', 'Vous avez quitté la salle.');
        }

        $em->flush();
        return $this->redirectToRoute('guardian_rooms');
    }
}