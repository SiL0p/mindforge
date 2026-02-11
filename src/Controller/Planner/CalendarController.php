<?php
// src/Controller/Planner/CalendarController.php
namespace App\Controller\Planner;

use App\Repository\Planner\ExamRepository;
use App\Repository\Planner\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planner/calendar')]
#[IsGranted('ROLE_USER')]
class CalendarController extends AbstractController
{
    #[Route('', name: 'app_planner_calendar', methods: ['GET'])]
    public function index(TaskRepository $taskRepo, ExamRepository $examRepo): Response
    {
        $user = $this->getUser();
        
        // Récupérer tâches avec date d'échéance
        $tasks = $taskRepo->findBy([
            'owner' => $user,
        ]);
        
        $exams = $examRepo->findBy([
            'owner' => $user,
        ]);

        // Formater pour FullCalendar ou similaire
        $events = [];
        
        foreach ($tasks as $task) {
            if ($task->getDueDate()) {
                $events[] = [
                    'id' => 'task_'.$task->getId(),
                    'title' => '📝 '.$task->getTitle(),
                    'start' => $task->getDueDate()->format('Y-m-d\TH:i:s'),
                    'color' => $this->getTaskColor($task->getStatus(), $task->getSubject()?->getColor()),
                    'url' => $this->generateUrl('app_planner_task_edit', ['id' => $task->getId()]),
                    'type' => 'task',
                    'extendedProps' => [
                        'status' => $task->getStatus(),
                        'priority' => $task->getPriority(),
                        'subject' => $task->getSubject()?->getName(),
                    ]
                ];
            }
        }

        foreach ($exams as $exam) {
            $events[] = [
                'id' => 'exam_'.$exam->getId(),
                'title' => '🎓 '.$exam->getTitle(),
                'start' => $exam->getExamDate()->format('Y-m-d\TH:i:s'),
                'end' => $exam->getDurationMinutes() 
                    ? $exam->getExamDate()->modify("+{$exam->getDurationMinutes()} minutes")->format('Y-m-d\TH:i:s')
                    : null,
                'color' => '#af17c2', // Magenta Energy pour les examens
                'url' => $this->generateUrl('app_planner_exam_edit', ['id' => $exam->getId()]),
                'type' => 'exam',
                'extendedProps' => [
                    'location' => $exam->getLocation(),
                    'importance' => $exam->getImportance(),
                    'subject' => $exam->getSubject()?->getName(),
                ]
            ];
        }

        return $this->render('planner/calendar/index.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    private function getTaskColor(string $status, ?string $subjectColor): string
    {
        return match($status) {
            'done' => '#10b981', // Vert
            'in_progress' => '#f59e0b', // Orange
            default => $subjectColor ?? '#6840d6', // Violet par défaut
        };
    }
}