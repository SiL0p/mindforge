<?php

namespace App\Service\Analyst;

use App\Entity\Planner\Task;

class DifficultyClassifierService
{
    private array $highKeywords = [
        'algorithm', 'architecture', 'optimization', 'thesis', 'complex', 'integration',
        'algorithme', 'architecture logicielle', 'optimisation', 'memoire', 'complexe', 'integration'
    ];

    private array $mediumKeywords = [
        'analysis', 'refactor', 'revision', 'report', 'assignment', 'prepare',
        'analyse', 'refactorisation', 'revision', 'rapport', 'devoir', 'preparer'
    ];

    private array $lowKeywords = [
        'read', 'watch', 'summary', 'quick', 'review',
        'lire', 'visionner', 'resume', 'rapide', 'revoir'
    ];

    public function classifyTask(Task $task): int
    {
        return $this->classifyText((string) $task->getTitle(), (string) $task->getDescription());
    }

    public function classifyText(string $title, ?string $description = null): int
    {
        $text = mb_strtolower(trim($title . ' ' . ($description ?? '')));

        $highScore = $this->scoreKeywords($text, $this->highKeywords);
        $mediumScore = $this->scoreKeywords($text, $this->mediumKeywords);
        $lowScore = $this->scoreKeywords($text, $this->lowKeywords);

        if ($highScore > 0 && $highScore >= $mediumScore) {
            return Task::PRIORITY_HIGH;
        }

        if ($mediumScore > 0) {
            return Task::PRIORITY_MEDIUM;
        }

        if ($lowScore > 0) {
            return Task::PRIORITY_LOW;
        }

        return Task::PRIORITY_MEDIUM;
    }

    private function scoreKeywords(string $text, array $keywords): int
    {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score++;
            }
        }

        return $score;
    }
}
