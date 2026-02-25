<?php
// src/Controller/Planner/TaskController.php
namespace App\Controller\Planner;

use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use App\Form\Planner\TaskType;
use App\Repository\Planner\TaskRepository;
use App\Service\Analyst\DifficultyClassifierService;
use App\Service\Analyst\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        DifficultyClassifierService $difficultyClassifierService
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
        $this->denyAccessUnlessGranted('TASK_EDIT', $task);

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
        $this->denyAccessUnlessGranted('TASK_DELETE', $task);

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
}