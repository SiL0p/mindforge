<?php
// src/Service/Analyst/ExamAIService.php
namespace App\Service\Analyst;

use App\Entity\Planner\Exam;
use App\Entity\Architect\User;
use App\Repository\Planner\TaskRepository;

class ExamAIService
{
    public function __construct(
        private TaskRepository $taskRepository
    ) {}

    public function generateRevisionPlan(Exam $exam): array
    {
        $now = new \DateTimeImmutable('today');
        $examDate = $exam->getExamDate()->setTime(0, 0, 0);
        
        $interval = $now->diff($examDate);
        $daysRemaining = (int) $interval->format('%R%a');

        if ($daysRemaining <= 0) {
            return [
                ['day' => 'Today', 'action' => 'Rest and review key concepts briefly. Good luck!']
            ];
        }

        $plan = [];
        $subjectName = $exam->getSubject() ? $exam->getSubject()->getName() : 'the subject';
        $importance = $exam->getImportance(); // 1 to 10

        if ($daysRemaining >= 10) {
            $plan[] = ['day' => 'Days 1–3', 'action' => 'Theory review'];
            $plan[] = ['day' => 'Days 4–6', 'action' => 'Practice exercises'];
            $plan[] = ['day' => 'Days 7–8', 'action' => 'Projects / real-world cases'];
            $plan[] = ['day' => 'Day ' . ($daysRemaining - 1), 'action' => 'Mock exam'];
            $plan[] = ['day' => 'Day ' . $daysRemaining, 'action' => 'Light review'];
        } elseif ($daysRemaining >= 5) {
            $theoryEnd = max(1, (int)($daysRemaining * 0.4));
            $practiceEnd = $theoryEnd + max(1, (int)($daysRemaining * 0.4));
            $plan[] = ['day' => "Days 1–$theoryEnd", 'action' => 'Theory review'];
            $plan[] = ['day' => "Days " . ($theoryEnd + 1) . "–$practiceEnd", 'action' => 'Practice exercises'];
            $plan[] = ['day' => 'Day ' . ($daysRemaining - 1), 'action' => 'Mock exam'];
            $plan[] = ['day' => 'Day ' . $daysRemaining, 'action' => 'Light review'];
        } else {
            // Less than 5 days
            $plan[] = ['day' => 'Days 1–' . max(1, ($daysRemaining - 2)), 'action' => "Intensive review and practice for $subjectName (Importance: $importance/10)"];
            if ($daysRemaining > 1) {
                $plan[] = ['day' => 'Day ' . ($daysRemaining - 1), 'action' => 'Mock exam'];
            }
            $plan[] = ['day' => 'Day ' . $daysRemaining, 'action' => 'Light review'];
        }

        return $plan;
    }

    public function calculatePreparationScore(Exam $exam, User $user): int
    {
        if (!$exam->getSubject()) {
            return 0;
        }

        $subject = $exam->getSubject();

        // 1. Check tasks progress for this subject
        $qb = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id) as totalTasks, SUM(CASE WHEN t.status = :done THEN 1 ELSE 0 END) as doneTasks')
            ->where('t.owner = :user')
            ->andWhere('t.subject = :subject')
            ->setParameter('user', $user)
            ->setParameter('subject', $subject)
            ->setParameter('done', 'done');

        $result = $qb->getQuery()->getSingleResult();
        $totalTasks = (int) ($result['totalTasks'] ?? 0);
        $doneTasks = (int) ($result['doneTasks'] ?? 0);

        $taskScore = 0;
        if ($totalTasks > 0) {
            $taskScore = ($doneTasks / $totalTasks) * 100;
        }
        
        $baseScore = $taskScore * 0.7; // Tasks give up to 70% of prep score
        $volumeBonus = min(30, $doneTasks * 5); // 5 points per done task, up to 30%

        $finalScore = (int) min(100, $baseScore + $volumeBonus);
        
        return $finalScore > 0 ? $finalScore : rand(5, 15); // Give a small default if no tasks to simulate initial prep status
    }

    public function predictSuccessProbability(Exam $exam, User $user): int
    {
        $prepScore = $this->calculatePreparationScore($exam, $user);
        
        // Importance acts as a complexity marker.
        $importance = $exam->getImportance(); // 1 to 10
        
        // Base probability is influenced strongly by prep score.
        $baseProbability = 30 + ($prepScore * 0.6); // 30% base + up to 60% from prep

        // High importance = slightly harder to get 100%, lower importance = easier
        $complexityPenalty = ($importance - 5) * 2; // e.g., 10 importance -> -10% modifier
        
        $predicted = (int) ($baseProbability - $complexityPenalty);

        return max(10, min(99, $predicted)); // Cap between 10% and 99%
    }
}
