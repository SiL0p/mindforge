<?php

namespace App\Controller\Planner\Admin;

use App\Entity\Planner\Exam;
use App\Entity\Planner\Subject;
use App\Entity\Planner\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PlannerAdminController extends AbstractController
{
    #[Route('/admin/planner', name: 'admin_planner_overview')]
    #[IsGranted('ROLE_ADMIN')]
    public function plannerOverview(EntityManagerInterface $em): Response
    {
        $taskRepo = $em->getRepository(Task::class);

        $totalTasks = $taskRepo->count([]);
        $todoTasks = $taskRepo->count(['status' => Task::STATUS_TODO]);
        $inProgressTasks = $taskRepo->count(['status' => Task::STATUS_IN_PROGRESS]);
        $doneTasks = $taskRepo->count(['status' => Task::STATUS_DONE]);
        $recentTasks = $taskRepo->findBy([], ['createdAt' => 'DESC'], 10);

        $totalSubjects = $em->getRepository(Subject::class)->count([]);

        $examError = null;
        $totalExams = 0;
        $upcomingExams = [];

        try {
            $examRepo = $em->getRepository(Exam::class);
            $totalExams = $examRepo->count([]);
            $upcomingExams = $examRepo->createQueryBuilder('e')
                ->where('e.examDate >= :now')
                ->setParameter('now', new \DateTimeImmutable())
                ->orderBy('e.examDate', 'ASC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        } catch (\Throwable $throwable) {
            $examError = 'Exam table/mapping is not available in this environment.';
        }

        $completionRate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

        return $this->render('admin/Planner/overview.html.twig', [
            'totalTasks' => $totalTasks,
            'todoTasks' => $todoTasks,
            'inProgressTasks' => $inProgressTasks,
            'doneTasks' => $doneTasks,
            'completionRate' => $completionRate,
            'totalSubjects' => $totalSubjects,
            'totalExams' => $totalExams,
            'upcomingExams' => $upcomingExams,
            'recentTasks' => $recentTasks,
            'examError' => $examError,
        ]);
    }
}
