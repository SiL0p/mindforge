<?php
// src/Controller/GuardianController.php

namespace App\Controller\Guardian;

use App\Entity\Architect\User;
use App\Entity\Guardian\FocusSession;
use App\Entity\Guardian\AiInsight;
use App\Entity\Guardian\Resource;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Community\ChatMessage;
use App\Entity\Planner\Subject;
use App\Entity\Planner\Task;
use App\Form\Guardian\ResourceType;
use App\Form\Guardian\VirtualRoomType;
use App\Repository\Guardian\FocusSessionRepository;
use App\Repository\Guardian\ResourceRepository;
use App\Repository\Guardian\VirtualRoomRepository;
use App\Repository\Planner\TaskRepository;
use App\Repository\Planner\SubjectRepository;
use App\Repository\Community\ChatMessageRepository;
use App\Service\Analyst\GamificationService;
use App\Service\Guardian\ExternalLearningResourceService;
use App\Service\Guardian\GuardianAiAssistant;
use Dompdf\Dompdf;
use Dompdf\Options;
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

    #[Route('/library/api/external-suggestions', name: 'guardian_library_api_external_suggestions', methods: ['GET'])]
    public function libraryApiExternalSuggestions(
        Request $request,
        ExternalLearningResourceService $externalLearningResourceService
    ): JsonResponse {
        $query = trim((string) $request->query->get('q', ''));
        $limit = max(1, min(10, (int) $request->query->get('limit', 6)));

        $suggestions = $externalLearningResourceService->fetchOpenLibrarySuggestions($query, $limit);

        return new JsonResponse([
            'success' => true,
            'query' => $query !== '' ? $query : 'study skills',
            'source' => 'openlibrary',
            'count' => count($suggestions),
            'suggestions' => $suggestions,
        ]);
    }

    #[Route('/library/upload', name: 'guardian_resource_upload', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT_PLUS')]
    public function uploadResource(
        Request $request,
        EntityManagerInterface $em,
        GuardianAiAssistant $guardianAiAssistant
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
            'external_ai_available' => $guardianAiAssistant->isExternalAiAvailable(),
        ]);
    }

    #[Route('/library/ai-generate', name: 'guardian_resource_ai_generate', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT_PLUS')]
    public function generateAiResource(
        Request $request,
        EntityManagerInterface $em,
        GuardianAiAssistant $guardianAiAssistant,
        SubjectRepository $subjectRepository
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];

        $token = (string) ($payload['_token'] ?? '');
        if (!$this->isCsrfTokenValid('guardian_ai_resource', $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid security token.',
            ], 403);
        }

        $subjectId = (int) ($payload['subject_id'] ?? 0);
        $description = trim((string) ($payload['description'] ?? ''));
        $studentDemand = trim((string) ($payload['student_demand'] ?? ''));
        $type = trim((string) ($payload['type'] ?? 'summary'));
        $titleHint = trim((string) ($payload['title_hint'] ?? ''));

        if ($subjectId <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please choose a subject first.',
            ], 422);
        }

        if ($description === '' || $studentDemand === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Description and student demand are required to generate an AI resource.',
            ], 422);
        }

        $subject = $subjectRepository->find($subjectId);
        if (!$subject) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Selected subject was not found.',
            ], 404);
        }

        $allowedTypes = $guardianAiAssistant->getAllowedResourceTypes();
        if (!in_array($type, $allowedTypes, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Type is not allowed. Allowed types: '.implode(', ', $allowedTypes).'.',
            ], 422);
        }

        $validation = $guardianAiAssistant->validateLearningResourceRequest(
            $subject->getName(),
            $description,
            $studentDemand,
            $type
        );

        if (($validation['valid'] ?? false) !== true) {
            return new JsonResponse([
                'success' => false,
                'message' => (string) ($validation['message'] ?? 'This request is not allowed.'),
            ], 422);
        }

        $draft = $guardianAiAssistant->generateLearningResource([
            'subject' => $subject->getName(),
            'description' => $description,
            'student_demand' => $studentDemand,
            'resource_type' => $type,
            'title_hint' => $titleHint,
        ]);

        $title = trim((string) ($draft['title'] ?? 'AI Generated Resource'));
        $content = trim((string) ($draft['content'] ?? ''));
        $generationSource = (string) ($draft['source'] ?? 'local');

        if ($content === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unable to generate resource content right now.',
            ], 500);
        }

        $baseName = (string) $this->slugger->slug($title !== '' ? $title : 'ai-resource');
        $baseName = $baseName !== '' ? $baseName : 'ai-resource';

        try {
            if ($type === 'pdf') {
                $filename = sprintf('%s-%s.pdf', $baseName, uniqid());
                $contentWithoutTopTitle = preg_replace('/^\s*#\s+[^\r\n]+\R+/u', '', $content, 1) ?? $content;
                $renderedContent = $this->simpleMarkdownToHtml($contentWithoutTopTitle);

                $html = sprintf(
                    '<html><head><meta charset="UTF-8"><style>body{font-family: DejaVu Sans, sans-serif; font-size:12px; line-height:1.6; color:#1f2937;} h1{font-size:22px; margin:0 0 8px;} h2{font-size:16px; margin:14px 0 6px;} h3{font-size:14px; margin:12px 0 4px;} p{margin:0 0 8px;} ul,ol{margin:0 0 10px 18px;} li{margin:0 0 4px;} .meta{font-size:11px;color:#6b7280;margin-bottom:12px;}</style></head><body><h1>%s</h1><div class="meta">Generated by MindForge AI Resource Builder</div>%s</body></html>',
                    htmlspecialchars($title !== '' ? $title : 'AI Generated Resource', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    $renderedContent
                );

                $options = new Options();
                $options->set('isRemoteEnabled', false);
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4');
                $dompdf->render();

                file_put_contents($this->resourcesDirectory.'/'.$filename, $dompdf->output());
            } else {
                $filename = sprintf('%s-%s.md', $baseName, uniqid());
                file_put_contents($this->resourcesDirectory.'/'.$filename, $content);
            }
        } catch (\Throwable) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unable to write generated file on server.',
            ], 500);
        }

        $resource = new Resource();
        $resource
            ->setTitle($title !== '' ? $title : 'AI Generated Resource')
            ->setDescription($description)
            ->setType($type)
            ->setAiGenerated(true)
            ->setSubject($subject)
            ->setUploader($this->getUser())
            ->setFilePath($filename);

        $em->persist($resource);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $generationSource === 'ai'
                ? 'Resource created successfully with external AI.'
                : 'Resource created successfully with local generator.',
            'resource_id' => $resource->getId(),
            'title' => $resource->getTitle(),
            'source' => $generationSource,
            'file_name' => $filename,
            'file_type' => $type,
            'redirect_url' => $this->generateUrl('guardian_library'),
        ]);
    }

    private function simpleMarkdownToHtml(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $html = [];
        $inUl = false;
        $inOl = false;

        $closeLists = static function () use (&$html, &$inUl, &$inOl): void {
            if ($inUl) {
                $html[] = '</ul>';
                $inUl = false;
            }
            if ($inOl) {
                $html[] = '</ol>';
                $inOl = false;
            }
        };

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                $closeLists();
                continue;
            }

            if (preg_match('/^###\s+(.*)$/', $line, $matches)) {
                $closeLists();
                $html[] = '<h3>'.htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h3>';
                continue;
            }

            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                $closeLists();
                $html[] = '<h2>'.htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h2>';
                continue;
            }

            if (preg_match('/^#\s+(.*)$/', $line, $matches)) {
                $closeLists();
                $html[] = '<h1>'.htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h1>';
                continue;
            }

            if (preg_match('/^-\s+(.*)$/', $line, $matches)) {
                if ($inOl) {
                    $html[] = '</ol>';
                    $inOl = false;
                }
                if (!$inUl) {
                    $html[] = '<ul>';
                    $inUl = true;
                }
                $html[] = '<li>'.htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $line, $matches)) {
                if ($inUl) {
                    $html[] = '</ul>';
                    $inUl = false;
                }
                if (!$inOl) {
                    $html[] = '<ol>';
                    $inOl = true;
                }
                $html[] = '<li>'.htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
                continue;
            }

            $closeLists();
            $html[] = '<p>'.htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
        }

        $closeLists();

        return implode('', $html);
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

        $extension = pathinfo((string) $resource->getFilePath(), PATHINFO_EXTENSION);
        $downloadFilename = $resource->getTitle();
        if ($extension !== '') {
            $downloadFilename .= '.'.$extension;
        }

        return (new BinaryFileResponse($filePath))
            ->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $downloadFilename
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
                'recommended_duration' => $this->getDurationForTaskPriority($task->getPriority()),
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
        TaskRepository $taskRepository,
        FocusSessionRepository $focusSessionRepository,
        GuardianAiAssistant $guardianAiAssistant,
        EntityManagerInterface $em
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

        $stats = [
            'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
            'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
            'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
            'per_task_totals' => $focusSessionRepository->getPerTaskTotalsByUser($user, 6),
        ];

        $taskContext = [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'priority' => $task->getPriority(),
            'estimated_minutes' => $task->getEstimatedMinutes(),
            'actual_minutes' => $task->getActualMinutes(),
        ];

        $aiRecommendation = $guardianAiAssistant->recommendDuration($taskContext, $stats);

        $this->persistAiInsight(
            $em,
            $user,
            $task,
            AiInsight::TYPE_RECOMMENDED_DURATION,
            [
                'task' => $taskContext,
                'stats' => $stats,
                'response' => $aiRecommendation,
            ],
            (string) ($aiRecommendation['source'] ?? 'rule')
        );

        return new JsonResponse([
            'success' => true,
            'task_id' => $task->getId(),
            'priority' => $task->getPriority(),
            'recommended_duration' => (int) ($aiRecommendation['duration'] ?? $this->getDurationForTaskPriority($task->getPriority())),
            'reason' => (string) ($aiRecommendation['reason'] ?? ''),
            'source' => (string) ($aiRecommendation['source'] ?? 'rule'),
        ]);
    }

    #[Route('/focus-timer/api/tips/{taskId}', name: 'guardian_focus_timer_api_tips', methods: ['GET'])]
    public function focusTimerApiTips(
        int $taskId,
        TaskRepository $taskRepository,
        FocusSessionRepository $focusSessionRepository,
        GuardianAiAssistant $guardianAiAssistant,
        EntityManagerInterface $em
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

        $stats = [
            'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
            'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
            'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
        ];

        $taskContext = [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'priority' => $task->getPriority(),
            'estimated_minutes' => $task->getEstimatedMinutes(),
            'actual_minutes' => $task->getActualMinutes(),
        ];

        $tipsPayload = $guardianAiAssistant->getFocusTips($taskContext, $stats);

        $this->persistAiInsight(
            $em,
            $user,
            $task,
            AiInsight::TYPE_FOCUS_TIPS,
            [
                'task' => $taskContext,
                'stats' => $stats,
                'response' => $tipsPayload,
            ],
            (string) ($tipsPayload['source'] ?? 'rule')
        );

        return new JsonResponse([
            'success' => true,
            'task_id' => $task->getId(),
            'tips' => $tipsPayload['tips'] ?? [],
            'motivation' => (string) ($tipsPayload['motivation'] ?? ''),
            'source' => (string) ($tipsPayload['source'] ?? 'rule'),
        ]);
    }

    #[Route('/focus-timer/api/daily-plan', name: 'guardian_focus_timer_api_daily_plan', methods: ['GET'])]
    public function focusTimerApiDailyPlan(
        TaskRepository $taskRepository,
        FocusSessionRepository $focusSessionRepository,
        GuardianAiAssistant $guardianAiAssistant,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $tasks = $taskRepository->findBy(['owner' => $user], ['createdAt' => 'DESC'], 12);
        $taskPayload = array_map(static function ($task): array {
            return [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'priority' => $task->getPriority(),
                'status' => $task->getStatus(),
                'estimated_minutes' => $task->getEstimatedMinutes(),
                'actual_minutes' => $task->getActualMinutes(),
            ];
        }, $tasks);

        $stats = [
            'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
            'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
            'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
            'per_task_totals' => $focusSessionRepository->getPerTaskTotalsByUser($user, 6),
        ];

        $planPayload = $guardianAiAssistant->buildDailyPlan($taskPayload, $stats);

        $this->persistAiInsight(
            $em,
            $user,
            null,
            AiInsight::TYPE_DAILY_PLAN,
            [
                'tasks' => $taskPayload,
                'stats' => $stats,
                'response' => $planPayload,
            ],
            (string) ($planPayload['source'] ?? 'rule')
        );

        return new JsonResponse([
            'success' => true,
            'plan' => $planPayload['plan'] ?? [],
            'source' => (string) ($planPayload['source'] ?? 'rule'),
        ]);
    }

    #[Route('/focus-timer/api/weekly-review', name: 'guardian_focus_timer_api_weekly_review', methods: ['GET'])]
    public function focusTimerApiWeeklyReview(
        FocusSessionRepository $focusSessionRepository,
        GuardianAiAssistant $guardianAiAssistant,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $stats = [
            'today_sessions' => $focusSessionRepository->getTodaySessionCountByUser($user),
            'week_focus_minutes' => $focusSessionRepository->getWeekDurationByUser($user),
            'total_focus_minutes' => $focusSessionRepository->getTotalDurationByUser($user),
            'per_task_totals' => $focusSessionRepository->getPerTaskTotalsByUser($user, 6),
        ];

        $recent = $focusSessionRepository->findRecentByUser($user, 8);
        $recentPayload = array_map(static function (FocusSession $session): array {
            return [
                'task' => $session->getTask()?->getTitle(),
                'duration' => $session->getDuration(),
                'timestamp' => $session->getTimestamp()?->format(DATE_ATOM),
            ];
        }, $recent);

        $review = $guardianAiAssistant->buildWeeklyReview($stats, $recentPayload);

        $this->persistAiInsight(
            $em,
            $user,
            null,
            AiInsight::TYPE_WEEKLY_REVIEW,
            [
                'stats' => $stats,
                'recent_sessions' => $recentPayload,
                'response' => $review,
            ],
            (string) ($review['source'] ?? 'rule')
        );

        return new JsonResponse([
            'success' => true,
            'summary' => (string) ($review['summary'] ?? ''),
            'wins' => $review['wins'] ?? [],
            'next_action' => (string) ($review['next_action'] ?? ''),
            'source' => (string) ($review['source'] ?? 'rule'),
        ]);
    }

    private function persistAiInsight(
        EntityManagerInterface $em,
        User $user,
        ?Task $task,
        string $type,
        array $payload,
        string $source
    ): void {
        $insight = new AiInsight();
        $insight
            ->setUser($user)
            ->setTask($task)
            ->setType($type)
            ->setSource($source)
            ->setPayload((string) json_encode($payload, JSON_UNESCAPED_UNICODE));

        $em->persist($insight);
        $em->flush();
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

        $requestedDuration = (int) ($payload['duration'] ?? 0);
        $targetDuration = $requestedDuration > 0
            ? max(1, min(240, $requestedDuration))
            : $this->getDurationForTaskPriority($task->getPriority());
        $effectiveDuration = max(1, min(240, min($elapsedMinutes, $targetDuration)));

        if ($focusSessionRepository->hasRecentDuplicate($user, $task, $effectiveDuration, 20)) {
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
            ->setDuration($effectiveDuration)
            ->setTimestamp(new \DateTimeImmutable())
            ->setSessionType('pomodoro');

        $newActualMinutes = ($task->getActualMinutes() ?? 0) + $effectiveDuration;
        $task->setActualMinutes($newActualMinutes);

        $previousStatus = $task->getStatus();
        if ($task->getEstimatedMinutes() !== null
            && $task->getEstimatedMinutes() > 0
            && $newActualMinutes >= $task->getEstimatedMinutes()
            && $task->getStatus() !== Task::STATUS_DONE) {
            $task->setStatus(Task::STATUS_DONE);
        } elseif ($task->getStatus() === Task::STATUS_TODO && $newActualMinutes >= 10) {
            $task->setStatus(Task::STATUS_IN_PROGRESS);
        }

        $statusChanged = $previousStatus !== $task->getStatus();

        $em->persist($focusSession);
        $gamificationPayload = $gamificationService->processFocusSession($user, $effectiveDuration);

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
                    'duration' => $effectiveDuration,
                    'target_duration' => $targetDuration,
                    'timestamp' => $focusSession->getTimestamp()?->format(DATE_ATOM),
                ],
                'task_progress' => [
                    'status' => $task->getStatus(),
                    'actual_minutes' => $newActualMinutes,
                    'estimated_minutes' => $task->getEstimatedMinutes(),
                    'status_changed' => $statusChanged,
                ],
                'gamification' => $gamificationPayload,
            ]);
        }

        $successMessage = 'Focus session saved successfully.';
        if ($statusChanged && $task->getStatus() === Task::STATUS_DONE) {
            $successMessage .= ' Task auto-marked as done.';
        } elseif ($statusChanged && $task->getStatus() === Task::STATUS_IN_PROGRESS) {
            $successMessage .= ' Task moved to in progress.';
        }

        $this->addFlash('success', $successMessage);
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