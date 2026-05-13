<?php
// src/Service/Analyst/ExamAIService.php
namespace App\Service\Analyst;

use App\Entity\Planner\Exam;
use App\Entity\Architect\User;
use App\Repository\Planner\TaskRepository;

class ExamAIService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL   = 'llama-3.1-8b-instant';
    private string $groqApiKey;

    public function __construct(
        private TaskRepository $taskRepository
    ) {
        $this->groqApiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: '';
    }

    // ─── Revision plan ─────────────────────────────────────────────────────

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
        $importance  = $exam->getImportance();

        if ($daysRemaining >= 10) {
            $plan[] = ['day' => 'Days 1–3', 'action' => 'Theory review'];
            $plan[] = ['day' => 'Days 4–6', 'action' => 'Practice exercises'];
            $plan[] = ['day' => 'Days 7–8', 'action' => 'Projects / real-world cases'];
            $plan[] = ['day' => 'Day ' . ($daysRemaining - 1), 'action' => 'Mock exam'];
            $plan[] = ['day' => 'Day ' . $daysRemaining,      'action' => 'Light review'];
        } elseif ($daysRemaining >= 5) {
            $theoryEnd   = max(1, (int)($daysRemaining * 0.4));
            $practiceEnd = $theoryEnd + max(1, (int)($daysRemaining * 0.4));
            $plan[] = ['day' => "Days 1–$theoryEnd",                              'action' => 'Theory review'];
            $plan[] = ['day' => 'Days ' . ($theoryEnd + 1) . "–$practiceEnd",     'action' => 'Practice exercises'];
            $plan[] = ['day' => 'Day ' . ($daysRemaining - 1),                    'action' => 'Mock exam'];
            $plan[] = ['day' => 'Day ' . $daysRemaining,                          'action' => 'Light review'];
        } else {
            $plan[] = ['day' => 'Days 1–' . max(1, ($daysRemaining - 2)), 'action' => "Intensive review and practice for $subjectName (Importance: $importance/10)"];
            if ($daysRemaining > 1) {
                $plan[] = ['day' => 'Day ' . ($daysRemaining - 1), 'action' => 'Mock exam'];
            }
            $plan[] = ['day' => 'Day ' . $daysRemaining, 'action' => 'Light review'];
        }

        return $plan;
    }

    // ─── Preparation score ─────────────────────────────────────────────────

    public function calculatePreparationScore(Exam $exam, User $user): int
    {
        if (!$exam->getSubject()) {
            return 0;
        }

        $subject = $exam->getSubject();

        $qb = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id) as totalTasks, SUM(CASE WHEN t.status = :done THEN 1 ELSE 0 END) as doneTasks')
            ->where('t.owner = :user')
            ->andWhere('t.subject = :subject')
            ->setParameter('user', $user)
            ->setParameter('subject', $subject)
            ->setParameter('done', 'done');

        $result     = $qb->getQuery()->getSingleResult();
        $doneTasks  = (int) ($result['doneTasks'] ?? 0);

        return (int) min(100, $doneTasks * 30);
    }

    // ─── Success probability ───────────────────────────────────────────────

    public function predictSuccessProbability(Exam $exam, User $user): int
    {
        $prepScore        = $this->calculatePreparationScore($exam, $user);
        $importance       = $exam->getImportance();
        $baseProbability  = 30 + ($prepScore * 0.6);
        $complexityPenalty = ($importance - 5) * 2;
        $predicted        = (int) ($baseProbability - $complexityPenalty);

        return max(10, min(99, $predicted));
    }

    // ─── Groq chatbot ──────────────────────────────────────────────────────

    public function chatWithGroq(Exam $exam, string $userMessage): string
    {
        $examTitle   = $exam->getTitle();
        $examSubject = $exam->getSubject() ? $exam->getSubject()->getName() : $examTitle;
        $examDate    = $exam->getExamDate() ? $exam->getExamDate()->format('Y-m-d H:i') : 'unknown date';
        $importance  = $exam->getImportance();

        // Build a strict system prompt so the AI refuses off-topic questions
        $systemPrompt = <<<PROMPT
You are a dedicated AI study assistant for ONE specific exam only.

Selected exam: "{$examTitle}"
Subject: {$examSubject}
Date: {$examDate}
Importance: {$importance}/10

YOUR RULES (follow them strictly, no exceptions):
1. You ONLY answer questions that are clearly related to the selected exam "{$examTitle}" and its subject "{$examSubject}".
2. If the user mentions or asks about any OTHER exam, subject, course, or topic that is NOT "{$examSubject}", you MUST refuse politely and say:
   "Sorry, I can only help with the '{$examTitle}' exam. Please ask me something related to this exam."
3. Do NOT answer general knowledge questions unrelated to {$examSubject}.
4. Be concise, clear, and encouraging when answering valid questions.
5. If a question is vague but could be about {$examSubject}, assume it is and answer it.
PROMPT;

        $payload = json_encode([
            'model'      => self::GROQ_MODEL,
            'max_tokens' => 512,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
        ]);

        $ch = curl_init(self::GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payload,
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->groqApiKey,
            ],
            CURLOPT_TIMEOUT         => 25,
            CURLOPT_SSL_VERIFYPEER  => false,   // fix Windows SSL cURL issue
            CURLOPT_SSL_VERIFYHOST  => false,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return "Connection error: $curlErr. Please try again.";
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg  = $errData['error']['message'] ?? "HTTP $httpCode";
            return "AI service error: $errMsg";
        }

        $data = json_decode($response, true);
        return trim($data['choices'][0]['message']['content'] ?? 'No response from AI.');
    }
}
