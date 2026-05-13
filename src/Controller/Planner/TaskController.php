<?php
// src/Controller/Planner/TaskController.php
namespace App\Controller\Planner;

use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use App\Entity\Planner\Subject;
use App\Form\Planner\TaskType;
use App\Repository\Planner\TaskRepository;
use App\Repository\Planner\SubjectRepository;
use App\Service\Analyst\DifficultyClassifierService;
use App\Service\Analyst\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planner/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('', name: 'app_planner_tasks', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        
        // Vue Kanban : grouper par statut
        $tasks = [
            'todo' => $taskRepository->findBy(
                ['owner' => $user, 'status' => Task::STATUS_TODO], 
                ['priority' => 'DESC', 'dueDate' => 'ASC']
            ),
            'in_progress' => $taskRepository->findBy(
                ['owner' => $user, 'status' => Task::STATUS_IN_PROGRESS], 
                ['priority' => 'DESC', 'dueDate' => 'ASC']
            ),
            'done' => $taskRepository->findBy(
                ['owner' => $user, 'status' => Task::STATUS_DONE], 
                ['completedAt' => 'DESC'], 
                20
            ),
        ];

        return $this->render('planner/task/index.html.twig', [
            'tasks' => $tasks,
            'statuses' => [
                Task::STATUS_TODO => ['label' => 'To do', 'color' => '#ef4444', 'icon' => 'circle'],
                Task::STATUS_IN_PROGRESS => ['label' => 'In progress', 'color' => '#f59e0b', 'icon' => 'spinner'],
                Task::STATUS_DONE => ['label' => 'Done', 'color' => '#10b981', 'icon' => 'check-circle'],
            ]
        ]);
    }

    #[Route('/new', name: 'app_planner_task_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        DifficultyClassifierService $difficultyClassifierService,
        TaskRepository $taskRepository
    ): Response
    {
        $task = new Task();
        $task->setOwner($this->getUser());
        
        // Voice-to-Task : pré-remplissage depuis paramètre URL
        if ($request->query->has('voice')) {
            $voiceText = trim($request->query->get('voice'));
            if (!empty($voiceText)) {
                $task->setTitle(substr($voiceText, 0, 150));
            }
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($task->getSubject()) {
                $stats = $taskRepository->getSubjectStatsForDate($this->getUser(), $task->getSubject(), $task->getDueDate());
                
                $willExceedCount = $stats['count'] >= 3;
                $willExceedTime = ($stats['minutes'] + (int)$task->getEstimatedMinutes()) > 180;

                if ($willExceedCount || $willExceedTime) {
                    $this->addFlash('danger', 'Limit reached: You cannot schedule more than 3 tasks or exceed 180 minutes for the same subject on the same day. Please do another subject!');
                    // Render the form again with the error flash
                    return $this->render('planner/task/new.html.twig', [
                        'form' => $form->createView(),
                        'task' => $task,
                        'isVoice' => $request->query->has('voice'),
                    ]);
                }
            }

            $task->setPriority($difficultyClassifierService->classifyTask($task));
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Task created successfully.');
            
            $redirect = $request->query->get('redirect', 'app_planner_tasks');
            return $this->redirectToRoute($redirect);
        }

        return $this->render('planner/task/new.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
            'isVoice' => $request->query->has('voice'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planner_task_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Task $task,
        EntityManagerInterface $em,
        DifficultyClassifierService $difficultyClassifierService
    ): Response
    {
        if ($task->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette tâche.');
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setPriority($difficultyClassifierService->classifyTask($task));
            $em->flush();
            $this->addFlash('success', 'Task updated successfully.');
            return $this->redirectToRoute('app_planner_tasks');
        }

        return $this->render('planner/task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_planner_task_status', methods: ['POST'])]
    public function updateStatus(
        Task $task,
        string $status,
        EntityManagerInterface $em,
        GamificationService $gamificationService
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        if ($task->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'You are not allowed to update this task.'], 403);
        }
        
        $validStatuses = [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE];
        if (!in_array($status, $validStatuses)) {
            return $this->json(['error' => 'Invalid status.'], 400);
        }

        $wasDone = $task->getStatus() === Task::STATUS_DONE;

        $task->setStatus($status);
        $em->flush();

        $gamificationPayload = null;
        if (!$wasDone && $status === Task::STATUS_DONE) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $gamificationPayload = $gamificationService->processCompletedTask($task, $user);
            }
        }

        return $this->json([
            'success' => true, 
            'newStatus' => $status,
            'message' => 'Status updated successfully.',
            'gamification' => $gamificationPayload,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_planner_task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if ($task->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette tâche.');
        }

        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Task deleted successfully.');
        }

        return $this->redirectToRoute('app_planner_tasks');
    }

    #[Route('/voice-create', name: 'app_planner_task_voice', methods: ['POST'])]
    public function voiceCreate(Request $request): Response
    {
        // Endpoint pour création vocale (reçoit audio ou texte transcrit)
        $transcribedText = $request->request->get('text');
        
        if (empty($transcribedText)) {
            return $this->json(['error' => 'No text received.'], 400);
        }

        // Redirection vers le formulaire avec le texte pré-rempli
        return $this->redirectToRoute('app_planner_task_new', [
            'voice' => substr($transcribedText, 0, 150),
        ]);
    }

    // ─── AJAX Table UI endpoints ───────────────────────────────────────────

    #[Route('/ajax/list', name: 'app_planner_tasks_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, TaskRepository $taskRepository): JsonResponse
    {
        $user = $this->getUser();
        $filter = $request->query->get('filter', 'all');
        $sort   = $request->query->get('sort', '');

        $criteria = ['owner' => $user];
        $validStatuses = [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE];
        if (in_array($filter, $validStatuses, true)) {
            $criteria['status'] = $filter;
        }

        $orderBy = $sort === 'due' ? ['dueDate' => 'ASC'] : ['id' => 'ASC'];
        $tasks = $taskRepository->findBy($criteria, $orderBy);

        $now = new \DateTimeImmutable();
        $rows = [];
        foreach ($tasks as $t) {
            $rows[] = [
                'id'          => $t->getId(),
                'title'       => $t->getTitle(),
                'description' => $t->getDescription(),
                'status'      => $t->getStatus(),
                'priority'    => $t->getPriority(),
                'dueDate'     => $t->getDueDate() ? $t->getDueDate()->format('Y-m-d H:i:s') : null,
                'ownerId'     => $t->getOwner()?->getId(),
                'duration'    => $t->getEstimatedMinutes(),
                'overdue'     => $t->getDueDate() && $t->getStatus() !== Task::STATUS_DONE && $t->getDueDate() < $now,
            ];
        }

        return $this->json($rows);
    }

    #[Route('/ajax/add', name: 'app_planner_tasks_ajax_add', methods: ['POST'])]
    public function ajaxAdd(
        Request $request,
        EntityManagerInterface $em,
        TaskRepository $taskRepository,
        SubjectRepository $subjectRepository,
        DifficultyClassifierService $difficultyClassifierService
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $task = new Task();
        $task->setOwner($user);
        $task->setTitle(substr(trim($data['title'] ?? ''), 0, 150));
        $task->setDescription(trim($data['description'] ?? '') ?: null);
        $task->setStatus(in_array($data['status'] ?? '', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE]) ? $data['status'] : Task::STATUS_TODO);

        $priority = (int)($data['priority'] ?? Task::PRIORITY_MEDIUM);
        $task->setPriority(in_array($priority, [1, 2, 3]) ? $priority : Task::PRIORITY_MEDIUM);

        if (!empty($data['dueDate'])) {
            try {
                $task->setDueDate(new \DateTimeImmutable($data['dueDate']));
            } catch (\Exception) {}
        }

        $duration = isset($data['duration']) ? (int)$data['duration'] : null;
        $task->setEstimatedMinutes($duration > 0 ? $duration : null);

        if (!empty($task->getTitle())) {
            $task->setPriority($difficultyClassifierService->classifyTask($task));
            $em->persist($task);
            $em->flush();

            $now = new \DateTimeImmutable();
            return $this->json([
                'success' => true,
                'task' => [
                    'id'          => $task->getId(),
                    'title'       => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status'      => $task->getStatus(),
                    'priority'    => $task->getPriority(),
                    'dueDate'     => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i:s') : null,
                    'ownerId'     => $task->getOwner()?->getId(),
                    'duration'    => $task->getEstimatedMinutes(),
                    'overdue'     => $task->getDueDate() && $task->getStatus() !== Task::STATUS_DONE && $task->getDueDate() < $now,
                ],
            ]);
        }

        return $this->json(['error' => 'Title is required.'], 400);
    }

    #[Route('/{id}/ajax/edit', name: 'app_planner_task_ajax_edit', methods: ['POST'])]
    public function ajaxEdit(
        Task $task,
        Request $request,
        EntityManagerInterface $em,
        DifficultyClassifierService $difficultyClassifierService
    ): JsonResponse {
        $user = $this->getUser();
        if ($task->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $task->setTitle(substr(trim($data['title']), 0, 150));
        }
        if (array_key_exists('description', $data)) {
            $task->setDescription(trim($data['description']) ?: null);
        }
        if (isset($data['status']) && in_array($data['status'], [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['priority']) && in_array((int)$data['priority'], [1, 2, 3])) {
            $task->setPriority((int)$data['priority']);
        }
        if (array_key_exists('dueDate', $data)) {
            try {
                $task->setDueDate(!empty($data['dueDate']) ? new \DateTimeImmutable($data['dueDate']) : null);
            } catch (\Exception) {}
        }
        if (array_key_exists('duration', $data)) {
            $dur = (int)$data['duration'];
            $task->setEstimatedMinutes($dur > 0 ? $dur : null);
        }

        $task->setPriority($difficultyClassifierService->classifyTask($task));
        $em->flush();

        $now = new \DateTimeImmutable();
        return $this->json([
            'success' => true,
            'task' => [
                'id'          => $task->getId(),
                'title'       => $task->getTitle(),
                'description' => $task->getDescription(),
                'status'      => $task->getStatus(),
                'priority'    => $task->getPriority(),
                'dueDate'     => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i:s') : null,
                'ownerId'     => $task->getOwner()?->getId(),
                'duration'    => $task->getEstimatedMinutes(),
                'overdue'     => $task->getDueDate() && $task->getStatus() !== Task::STATUS_DONE && $task->getDueDate() < $now,
            ],
        ]);
    }

    #[Route('/{id}/ajax/delete', name: 'app_planner_task_ajax_delete', methods: ['DELETE'])]
    public function ajaxDelete(Task $task, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($task->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $em->remove($task);
        $em->flush();

        return $this->json(['success' => true]);
    }
}