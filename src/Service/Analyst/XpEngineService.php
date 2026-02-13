<?php

namespace App\Service\Analyst;

use App\Entity\Planner\Task;

class XpEngineService
{
    public function calculateForTask(Task $task): int
    {
        $timeSpent = $task->getActualMinutes() ?? $task->getEstimatedMinutes() ?? 0;
        $difficulty = $task->getPriority() ?? Task::PRIORITY_MEDIUM;

        $timeSpent = max(0, (int) $timeSpent);
        $difficulty = max(Task::PRIORITY_LOW, min(Task::PRIORITY_HIGH, (int) $difficulty));

        return $timeSpent * $difficulty;
    }
}
