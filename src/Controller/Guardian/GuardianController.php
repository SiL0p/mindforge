<?php
// src/Controller/GuardianController.php

namespace App\Controller\Guardian;

use App\Entity\Architect\User;
use App\Entity\Guardian\FocusSession;
use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Community\ChatMessage;
use App\Entity\Planner\Subject;
use App\Form\Guardian\ResourceType;
use App\Form\Guardian\VirtualRoomType;
use App\Repository\Guardian\FocusSessionRepository;
use App\Repository\Guardian\ResourceRepository;
use App\Repository\Guardian\VirtualRoomRepository;
use App\Repository\Planner\TaskRepository;
use App\Repository\Planner\SubjectRepository;
use App\Repository\Community\ChatMessageRepository;
use App\Service\Analyst\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        return $this->render('user/guardian/library.html.twig', [
            'resources' => $repo->findByFilters($filters),
            'subjects' => $subjectRepo->findAll(),
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

        return $this->render('user/guardian/resource_upload.html.twig', [
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
        $subjectId = $subjectId !== null && $subjectId !== '' ? (int) $subjectId : null;
        
        return $this->render('user/guardian/rooms.html.twig', [
            'rooms' => $repo->findActiveRooms($subjectId),
            'subjects' => $subjectRepo->findAll(),
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

        return $this->render('user/guardian/room_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/rooms/{id}', name: 'guardian_room_detail', methods: ['GET'])]
    public function roomDetail(
        VirtualRoom $room,
        ChatMessageRepository $messageRepo
    ): Response
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

        return $this->render('user/guardian/room_detail.html.twig', [
            'room' => $room,
            'is_participant' => $isParticipant,
            'messages' => $messageRepo->findByRoom($room),
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

    // ==================== FOCUS TIMER (POMODORO) ====================

    #[Route('/focus-timer', name: 'guardian_focus_timer', methods: ['GET'])]
    public function focusTimer(
        Request $request,
        TaskRepository $taskRepository,
        FocusSessionRepository $focusSessionRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $selectedTaskId = (int) $request->query->get('task', 0);

        return $this->render('user/guardian/focus_timer.html.twig', [
            'tasks' => $taskRepository->findBy(['owner' => $user], ['createdAt' => 'DESC'], 100),
            'recent_sessions' => $focusSessionRepository->findRecentByUser($user, 12),
            'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
            'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
            'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
            'per_task_totals' => $focusSessionRepository->getPerTaskTotalsByUser($user, 6),
            'selected_task_id' => $selectedTaskId,
        ]);
    }

    #[Route('/focus-timer/api/overview', name: 'guardian_focus_timer_api_overview', methods: ['GET'])]
    public function focusTimerApiOverview(
        FocusSessionRepository $focusSessionRepository,
        TaskRepository $taskRepository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $tasks = $taskRepository->findBy(['owner' => $user], ['createdAt' => 'DESC'], 100);
        $taskPayload = array_map(function ($task): array {
            return [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'priority' => $task->getPriority(),
                'recommended_duration' => $task->getEstimatedMinutes() ?? $this->getDurationForTaskPriority($task->getPriority()),
            ];
        }, $tasks);

        $sessions = $focusSessionRepository->findRecentByUser($user, 12);
        $sessionPayload = array_map(static function (FocusSession $session): array {
            return [
                'id' => $session->getId(),
                'task_id' => $session->getTask()?->getId(),
                'task_title' => $session->getTask()?->getTitle(),
                'duration' => $session->getDuration(),
                'timestamp' => $session->getTimestamp()?->format(DATE_ATOM),
            ];
        }, $sessions);

        return new JsonResponse([
            'success' => true,
            'stats' => [
                'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
                'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
                'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
                'per_task_totals' => $focusSessionRepository->getPerTaskTotalsByUser($user, 6),
            ],
            'tasks' => $taskPayload,
            'recent_sessions' => $sessionPayload,
        ]);
    }

    #[Route('/focus-timer/api/recommended-duration/{taskId}', name: 'guardian_focus_timer_api_recommended_duration', methods: ['GET'])]
    public function focusTimerApiRecommendedDuration(
        int $taskId,
        TaskRepository $taskRepository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $task = $taskRepository->findOneBy([
            'id' => $taskId,
            'owner' => $user,
        ]);

        if (!$task) {
            return new JsonResponse(['success' => false, 'message' => 'Selected task was not found.'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'task_id' => $task->getId(),
            'priority' => $task->getPriority(),
            'recommended_duration' => $task->getEstimatedMinutes() ?? $this->getDurationForTaskPriority($task->getPriority()),
        ]);
    }

    #[Route('/focus-timer/api/log', name: 'guardian_focus_timer_api_log', methods: ['POST'])]
    #[Route('/focus-sessions/log', name: 'guardian_focus_session_log', methods: ['POST'])]
    public function logFocusSession(
        Request $request,
        FocusSessionRepository $focusSessionRepository,
        TaskRepository $taskRepository,
        EntityManagerInterface $em,
        GamificationService $gamificationService
    ): Response {
        $user = $this->getUser();
        $expectsJson = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Content-Type'), 'application/json');

        if (!$user instanceof User) {
            if ($expectsJson) {
                return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
            }

            return $this->redirectToRoute('app_login');
        }

        $payload = [];
        if ($expectsJson && $request->getContent()) {
            $payload = json_decode($request->getContent(), true) ?? [];
        } else {
            $payload = $request->request->all();
        }

        $token = (string) ($payload['_token'] ?? $request->request->get('_token'));
        if (!$this->isCsrfTokenValid('focus_session_log', $token)) {
            $message = 'Invalid security token.';

            if ($expectsJson) {
                return new JsonResponse(['success' => false, 'message' => $message], 403);
            }

            $this->addFlash('error', $message);
            return $this->redirectToRoute('guardian_focus_timer');
        }

        $taskId = (int) ($payload['task_id'] ?? 0);
        $elapsedMinutes = (int) ($payload['elapsed_minutes'] ?? 0);
        $clientSessionId = trim((string) ($payload['client_session_id'] ?? ''));

        if ($taskId < 1 || $elapsedMinutes < 1) {
            $message = 'Please select a task and run at least 1 minute before saving.';

            if ($expectsJson) {
                return new JsonResponse(['success' => false, 'message' => $message], 422);
            }

            $this->addFlash('error', $message);
            return $this->redirectToRoute('guardian_focus_timer');
        }

        if ($clientSessionId !== '') {
            $processedSessions = $request->getSession()->get('guardian_focus_processed', []);
            if (in_array($clientSessionId, $processedSessions, true)) {
                $message = 'This focus session was already saved.';

                if ($expectsJson) {
                    return new JsonResponse(['success' => false, 'message' => $message], 409);
                }

                $this->addFlash('warning', $message);
                return $this->redirectToRoute('guardian_focus_timer', ['task' => $taskId]);
            }
        }

        $task = $taskRepository->findOneBy([
            'id' => $taskId,
            'owner' => $user,
        ]);

        if (!$task) {
            $message = 'Selected task was not found.';

            if ($expectsJson) {
                return new JsonResponse(['success' => false, 'message' => $message], 404);
            }

            $this->addFlash('error', $message);
            return $this->redirectToRoute('guardian_focus_timer');
        }

        $calculatedDuration = $task->getEstimatedMinutes() ?? $this->getDurationForTaskPriority($task->getPriority());
        $duration = $elapsedMinutes > 0 ? $elapsedMinutes : $calculatedDuration;

        if ($focusSessionRepository->hasRecentDuplicate($user, $task, $duration, 20)) {
            $message = 'Duplicate session detected. Please wait a few seconds before saving again.';

            if ($expectsJson) {
                return new JsonResponse(['success' => false, 'message' => $message], 409);
            }

            $this->addFlash('warning', $message);
            return $this->redirectToRoute('guardian_focus_timer', ['task' => $task->getId()]);
        }

        $focusSession = new FocusSession();
        $focusSession
            ->setUser($user)
            ->setTask($task)
            ->setDuration($duration)
            ->setTimestamp(new \DateTimeImmutable())
            ->setSessionType('pomodoro');

        $task->setActualMinutes(($task->getActualMinutes() ?? 0) + $duration);

        $em->persist($focusSession);
        $gamificationPayload = $gamificationService->processFocusSession($user, $duration);

        if ($clientSessionId !== '') {
            $processedSessions = $request->getSession()->get('guardian_focus_processed', []);
            $processedSessions[] = $clientSessionId;
            $processedSessions = array_slice(array_values(array_unique($processedSessions)), -50);
            $request->getSession()->set('guardian_focus_processed', $processedSessions);
        }

        if ($expectsJson) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Focus session saved successfully.',
                'session' => [
                    'task' => $task->getTitle(),
                    'duration' => $duration,
                    'timestamp' => $focusSession->getTimestamp()?->format(DATE_ATOM),
                ],
                'gamification' => $gamificationPayload,
            ]);
        }

        $this->addFlash('success', 'Focus session saved successfully.');
        return $this->redirectToRoute('guardian_focus_timer');
    }

    private function getDurationForTaskPriority(?int $priority): int
    {
        return match ($priority) {
            3 => 50,
            2 => 35,
            default => 25,
        };
    }
}