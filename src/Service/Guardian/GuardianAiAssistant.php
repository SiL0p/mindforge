<?php

declare(strict_types=1);

namespace App\Service\Guardian;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GuardianAiAssistant
{
    public const ALLOWED_RESOURCE_TYPES = ['pdf', 'summary', 'cheat_sheet', 'exercise'];

    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const GEMINI_ENDPOINT_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const CLAUDE_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const QUOTE_ENDPOINT = 'https://zenquotes.io/api/random';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $provider = 'openai',
        private string $openAiApiKey = '',
        private string $openAiModel = 'gpt-4o-mini',
        private string $geminiApiKey = '',
        private string $geminiModel = 'gemini-1.5-flash',
        private string $claudeApiKey = '',
        private string $claudeModel = 'claude-3-5-haiku-latest'
    ) {
    }

    public function recommendDuration(array $taskContext, array $stats): array
    {
        $ruleBased = $this->ruleBasedDuration((int) ($taskContext['priority'] ?? 2));

        if (!$this->isAiConfigured()) {
            return [
                'duration' => $ruleBased,
                'reason' => 'Rule-based fallback (AI key not configured).',
                'source' => 'rule',
            ];
        }

        $prompt = [
            'task' => [
                'title' => (string) ($taskContext['title'] ?? ''),
                'priority' => (int) ($taskContext['priority'] ?? 2),
                'estimated_minutes' => $taskContext['estimated_minutes'] ?? null,
                'actual_minutes' => $taskContext['actual_minutes'] ?? null,
            ],
            'user_stats' => $stats,
            'constraints' => [
                'min_duration' => 15,
                'max_duration' => 90,
                'step' => 5,
            ],
        ];

        $system = 'You are a productivity assistant for a Pomodoro app. Return strictly valid JSON with fields: duration (int), reason (string). No markdown.';
        $user = 'Recommend a focus duration in minutes based on this context: '.json_encode($prompt, JSON_UNESCAPED_UNICODE);

        $data = $this->callStructuredAi($system, $user, 220);
        if (!$data || !isset($data['duration'])) {
            return [
                'duration' => $ruleBased,
                'reason' => 'Rule-based fallback (AI parse error).',
                'source' => 'rule',
            ];
        }

        $duration = (int) $data['duration'];
        $bounded = max(15, min(90, $duration));
        $rounded = (int) (round($bounded / 5) * 5);

        return [
            'duration' => $rounded,
            'reason' => (string) ($data['reason'] ?? 'AI recommendation.'),
            'source' => 'ai',
        ];
    }

    public function getFocusTips(array $taskContext, array $stats): array
    {
        $fallbackTips = [
            'Start with a single clear outcome for this session.',
            'Mute distractions and keep only one tab/app open.',
            'Take a 5-minute break after each completed session.',
        ];

        $quote = $this->fetchMotivationQuote();

        if (!$this->isAiConfigured()) {
            return [
                'tips' => $fallbackTips,
                'motivation' => $quote,
                'source' => 'rule',
            ];
        }

        $context = [
            'task' => $taskContext,
            'stats' => $stats,
        ];

        $system = 'You are a concise study coach. Return strictly valid JSON with keys: tips (array of 3 short strings), motivation (string). No markdown.';
        $user = 'Generate focus tips for this context: '.json_encode($context, JSON_UNESCAPED_UNICODE);

        $data = $this->callStructuredAi($system, $user, 260);
        if (!$data || !isset($data['tips']) || !is_array($data['tips'])) {
            return [
                'tips' => $fallbackTips,
                'motivation' => $quote,
                'source' => 'rule',
            ];
        }

        $tips = array_values(array_slice(array_filter(array_map(static fn ($tip) => trim((string) $tip), $data['tips'])), 0, 3));
        if (count($tips) < 3) {
            $tips = $fallbackTips;
        }

        return [
            'tips' => $tips,
            'motivation' => (string) ($data['motivation'] ?? $quote),
            'source' => 'ai',
        ];
    }

    public function buildDailyPlan(array $tasks, array $stats): array
    {
        $defaultPlan = [
            'Start with one high-priority task block.',
            'Run two medium tasks in focused sessions.',
            'Finish with a short review and tomorrow prep.',
        ];

        if (!$this->isAiConfigured()) {
            return [
                'plan' => $defaultPlan,
                'source' => 'rule',
            ];
        }

        $context = [
            'tasks' => array_slice($tasks, 0, 10),
            'stats' => $stats,
        ];

        $system = 'You are a planning assistant. Return strictly valid JSON with key plan (array of 3 short actionable items). No markdown.';
        $user = 'Create a practical daily focus plan: '.json_encode($context, JSON_UNESCAPED_UNICODE);

        $data = $this->callStructuredAi($system, $user, 260);
        if (!$data || !isset($data['plan']) || !is_array($data['plan'])) {
            return [
                'plan' => $defaultPlan,
                'source' => 'rule',
            ];
        }

        $plan = array_values(array_slice(array_filter(array_map(static fn ($item) => trim((string) $item), $data['plan'])), 0, 3));
        if (count($plan) < 3) {
            $plan = $defaultPlan;
        }

        return [
            'plan' => $plan,
            'source' => 'ai',
        ];
    }

    public function buildWeeklyReview(array $stats, array $recentSessions): array
    {
        $fallback = [
            'summary' => 'You are building consistency with regular focus blocks this week.',
            'wins' => [
                'Keep your top task streak active.',
                'Protect one distraction-free block daily.',
            ],
            'next_action' => 'Schedule one 35-minute session on your highest-priority task today.',
            'source' => 'rule',
        ];

        if (!$this->isAiConfigured()) {
            return $fallback;
        }

        $context = [
            'stats' => $stats,
            'recent_sessions' => array_slice($recentSessions, 0, 8),
        ];

        $system = 'You are a concise productivity coach. Return strictly valid JSON with keys: summary (string), wins (array of 2 short strings), next_action (string). No markdown.';
        $user = 'Create a weekly focus review from this context: '.json_encode($context, JSON_UNESCAPED_UNICODE);

        $data = $this->callStructuredAi($system, $user, 260);
        if (!$data || !isset($data['summary']) || !isset($data['next_action'])) {
            return $fallback;
        }

        $wins = [];
        if (isset($data['wins']) && is_array($data['wins'])) {
            $wins = array_values(array_slice(array_filter(array_map(static fn ($item) => trim((string) $item), $data['wins'])), 0, 2));
        }

        if (count($wins) < 2) {
            $wins = $fallback['wins'];
        }

        return [
            'summary' => trim((string) $data['summary']) !== '' ? (string) $data['summary'] : $fallback['summary'],
            'wins' => $wins,
            'next_action' => trim((string) $data['next_action']) !== '' ? (string) $data['next_action'] : $fallback['next_action'],
            'source' => 'ai',
        ];
    }

    public function generateLearningResource(array $context): array
    {
        $subject = trim((string) ($context['subject'] ?? 'General'));
        $description = trim((string) ($context['description'] ?? ''));
        $studentDemand = trim((string) ($context['student_demand'] ?? ''));
        $resourceType = trim((string) ($context['resource_type'] ?? 'summary'));
        $titleHint = trim((string) ($context['title_hint'] ?? ''));

        $fallbackTitle = $titleHint !== ''
            ? $titleHint
            : sprintf('%s - Study Resource', $subject);

        $fallbackContent = $this->buildLocalResourceContent($subject, $description, $studentDemand, $resourceType, $fallbackTitle);

        if (!$this->isAiConfigured()) {
            return [
                'title' => $fallbackTitle,
                'content' => $fallbackContent,
                'source' => 'local',
            ];
        }

        $prompt = [
            'subject' => $subject,
            'description' => $description,
            'student_demand' => $studentDemand,
            'resource_type' => $resourceType,
            'title_hint' => $titleHint,
            'constraints' => [
                'format' => 'markdown',
                'content_length_target' => '350-700 words',
                'include_sections' => ['Learning objective', 'Core explanation', 'Practical steps', 'Self-check questions'],
            ],
        ];

        $system = 'You are an educational content assistant. Return strictly valid JSON with keys: title (string), content (string markdown). No markdown outside JSON.';
        $user = 'Generate a complete learning resource from this context: '.json_encode($prompt, JSON_UNESCAPED_UNICODE);

        $data = $this->callStructuredAi($system, $user, 1100);
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            return [
                'title' => $fallbackTitle,
                'content' => $fallbackContent,
                'source' => 'local',
            ];
        }

        $title = trim((string) $data['title']);
        $content = trim((string) $data['content']);

        if ($title === '') {
            $title = $fallbackTitle;
        }

        if ($content === '') {
            $content = $fallbackContent;
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'content' => mb_substr($content, 0, 12000),
            'source' => 'ai',
        ];
    }

    public function getAllowedResourceTypes(): array
    {
        return self::ALLOWED_RESOURCE_TYPES;
    }

    public function validateLearningResourceRequest(string $subject, string $description, string $studentDemand, string $resourceType): array
    {
        if (!in_array($resourceType, self::ALLOWED_RESOURCE_TYPES, true)) {
            return [
                'valid' => false,
                'message' => 'Requested file type is not allowed for AI generation.',
            ];
        }

        $combined = mb_strtolower(trim($subject.' '.$description.' '.$studentDemand));
        $blockedTopics = [
            'football',
            'soccer',
            'fifa',
            'nba',
            'nfl',
            'tennis',
            'boxing',
            'ufc',
            'transfer market',
            'premier league',
            'champions league',
        ];

        foreach ($blockedTopics as $topic) {
            if (str_contains($combined, $topic)) {
                return [
                    'valid' => false,
                    'message' => 'Only study-related educational content is allowed. Sports topics are blocked.',
                ];
            }
        }

        $studyAllowlist = [
            'study',
            'learning',
            'learn',
            'education',
            'course',
            'lesson',
            'revision',
            'exercise',
            'exam',
            'quiz',
            'homework',
            'assignment',
            'school',
            'university',
            'student',
            'teacher',
            'class',
            'math',
            'mathematics',
            'algebra',
            'geometry',
            'physics',
            'chemistry',
            'biology',
            'history',
            'geography',
            'literature',
            'programming',
            'informatique',
            'étude',
            'etud',
            'apprentissage',
            'éducation',
            'education',
            'cours',
            'leçon',
            'lecon',
            'révision',
            'revision',
            'exercice',
            'devoir',
            'contrôle',
            'controle',
            'bac',
            'licence',
            'master',
            'matière',
            'matiere',
        ];

        $hasStudySignal = false;
        foreach ($studyAllowlist as $token) {
            if (str_contains($combined, $token)) {
                $hasStudySignal = true;
                break;
            }
        }

        if (!$hasStudySignal) {
            return [
                'valid' => false,
                'message' => 'Only study-related educational content is allowed. Please provide an academic learning request.',
            ];
        }

        return [
            'valid' => true,
            'message' => null,
        ];
    }

    private function buildLocalResourceContent(string $subject, string $description, string $studentDemand, string $resourceType, string $title): string
    {
        $need = $studentDemand !== '' ? $studentDemand : 'Understand the core concepts and practice with exercises.';
        $desc = $description !== '' ? $description : 'Structured explanation with practical examples.';

        $subjectLc = mb_strtolower($subject);
        $needLc = mb_strtolower($need.' '.$desc);

        if (
            str_contains($subjectLc, 'math')
            && (
                str_contains($needLc, 'espace vectoriel')
                || str_contains($needLc, 'vector space')
                || str_contains($needLc, 'algèbre linéaire')
                || str_contains($needLc, 'algebre lineaire')
            )
        ) {
            return $this->buildVectorSpaceExercisePack($title, $resourceType);
        }

        $intro = sprintf(
            'This %s resource focuses on **%s** and targets the student need: **%s**.',
            $resourceType,
            $subject,
            $need
        );

        return implode("\n", [
            '# '.$title,
            '',
            '## Learning Objective',
            $intro,
            '',
            '## Context',
            $desc,
            '',
            '## Core Explanation',
            '- Identify key definitions and notation for the topic.',
            '- Break the topic into 3 short concept blocks.',
            '- For each block, write one simple example and one common mistake.',
            '',
            '## Practical Steps',
            '1. Review definitions for 10 minutes.',
            '2. Solve 2 guided examples step by step.',
            '3. Complete 3 independent exercises with correction.',
            '4. Summarize what to remember in 5 bullet points.',
            '',
            '## Practice Exercises',
            '1. Basic exercise: apply the main definition in a direct case.',
            '2. Medium exercise: combine two concepts in one problem.',
            '3. Challenge exercise: justify the method and verify the result.',
            '',
            '## Self-check Questions',
            '- Can I explain the concept in my own words?',
            '- Can I solve a similar exercise without help?',
            '- Which step is still unclear and needs revision?',
            '',
            '## 30-Minute Study Plan',
            '- 10 min: concept recap',
            '- 15 min: exercise solving',
            '- 5 min: correction + notes',
        ]);
    }

    private function buildVectorSpaceExercisePack(string $title, string $resourceType): string
    {
        $focus = $resourceType === 'exercise'
            ? 'Pack orienté exercices'
            : 'Fiche + exercices guidés';

        return implode("\n", [
            '# '.$title,
            '',
            '## Objectif',
            $focus.' sur les **espaces vectoriels** (niveau licence): tester si un ensemble est un sous-espace, manipuler des familles génératrices/libres, base et dimension.',
            '',
            '## Rappels essentiels',
            '- Un sous-ensemble $F$ est un sous-espace de $E$ si: (i) $0 \in F$, (ii) $u,v \in F \Rightarrow u+v \in F$, (iii) $\lambda \in \mathbb{R}, u \in F \Rightarrow \lambda u \in F$.',
            '- Une famille est **libre** si la combinaison linéaire nulle est triviale.',
            '- Une base est une famille libre et génératrice.',
            '- Dimension: nombre de vecteurs d\'une base.',
            '',
            '## Exercice 1 — Sous-espace ?',
            'Dans $\mathbb{R}^3$, étudier $F=\{(x,y,z)\mid x-2y+z=0\}$.',
            '**Correction courte :** Oui, c\'est le noyau d\'une forme linéaire $\varphi(x,y,z)=x-2y+z$, donc sous-espace.',
            '',
            '## Exercice 2 — Contre-exemple',
            'Dans $\mathbb{R}^2$, étudier $G=\{(x,y)\mid x+y=1\}$.',
            '**Correction courte :** Non, $0\notin G$ car $0+0\neq 1$.',
            '',
            '## Exercice 3 — Liberté linéaire',
            'Étudier la liberté de $u_1=(1,0,1)$, $u_2=(2,1,3)$, $u_3=(0,1,1)$ dans $\mathbb{R}^3$.',
            '**Correction courte :** Poser $a u_1+b u_2+c u_3=0$. Système:',
            '- $a+2b=0$',
            '- $b+c=0$',
            '- $a+3b+c=0$',
            'On obtient $a=b=c=0$ donc famille libre.',
            '',
            '## Exercice 4 — Base et dimension',
            'Soit $H=\{(x,y,z,t)\in\mathbb{R}^4\mid x+y+z+t=0\}$. Trouver une base et la dimension.',
            '**Correction courte :** Paramétrer $x=-y-z-t$:',
            '- $(x,y,z,t)=y(-1,1,0,0)+z(-1,0,1,0)+t(-1,0,0,1)$',
            'Base possible $\{(-1,1,0,0),(-1,0,1,0),(-1,0,0,1)\}$, donc $\dim(H)=3$.',
            '',
            '## Exercice 5 — Génératrice minimale',
            'Dans $\mathbb{R}^2$, simplifier la famille $S=\{(1,1),(2,2),(1,0),(0,1)\}$.',
            '**Correction courte :** $(2,2)=2(1,1)$ est redondant, $(1,1)=(1,0)+(0,1)$. Base finale: $\{(1,0),(0,1)\}$.',
            '',
            '## Exercice 6 — Vrai/Faux',
            '1) Toute famille de 4 vecteurs de $\mathbb{R}^3$ est liée.',
            '2) Toute famille libre de $\mathbb{R}^3$ de 3 vecteurs est une base.',
            '3) Un sous-espace de dimension 2 de $\mathbb{R}^3$ est un plan passant par l\'origine.',
            '**Réponses :** 1) Vrai, 2) Vrai, 3) Vrai.',
            '',
            '## Plan de travail (45 min)',
            '- 10 min: rappels + définitions.',
            '- 25 min: exercices 1 à 4.',
            '- 10 min: correction + fiche erreurs fréquentes.',
            '',
            '## Erreurs fréquentes',
            '- Oublier de vérifier la présence du vecteur nul.',
            '- Confondre famille génératrice et famille libre.',
            '- Conclure trop vite sans résoudre le système linéaire.',
        ]);
    }

    private function isAiConfigured(): bool
    {
        return match ($this->getProvider()) {
            'gemini' => trim($this->geminiApiKey) !== '',
            'claude' => trim($this->claudeApiKey) !== '',
            default => trim($this->openAiApiKey) !== '',
        };
    }

    public function isExternalAiAvailable(): bool
    {
        return $this->isAiConfigured();
    }

    private function fetchMotivationQuote(): string
    {
        try {
            $response = $this->httpClient->request('GET', self::QUOTE_ENDPOINT, [
                'timeout' => 4,
            ]);

            if ($response->getStatusCode() !== 200) {
                return 'Small progress every day builds strong momentum.';
            }

            $payload = $response->toArray(false);
            if (!is_array($payload) || !isset($payload[0]['q'])) {
                return 'Small progress every day builds strong momentum.';
            }

            $quote = (string) ($payload[0]['q'] ?? '');
            $author = (string) ($payload[0]['a'] ?? 'Unknown');

            return trim($quote) !== '' ? sprintf('%s — %s', $quote, $author) : 'Small progress every day builds strong momentum.';
        } catch (\Throwable) {
            return 'Small progress every day builds strong momentum.';
        }
    }

    private function callStructuredAi(string $system, string $user, int $maxTokens = 220): ?array
    {
        return match ($this->getProvider()) {
            'gemini' => $this->callGemini($system, $user, $maxTokens),
            'claude' => $this->callClaude($system, $user, $maxTokens),
            default => $this->callOpenAi($system, $user, $maxTokens),
        };
    }

    private function callOpenAi(string $system, string $user, int $maxTokens = 220): ?array
    {
        try {
            $response = $this->httpClient->request('POST', self::OPENAI_ENDPOINT, [
                'timeout' => 12,
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $payload = $response->toArray(false);
            $raw = (string) ($payload['choices'][0]['message']['content'] ?? '');
            return $this->decodeJsonText($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function callGemini(string $system, string $user, int $maxTokens = 220): ?array
    {
        try {
            $endpoint = sprintf(self::GEMINI_ENDPOINT_TEMPLATE, rawurlencode($this->geminiModel));

            $response = $this->httpClient->request('POST', $endpoint, [
                'timeout' => 12,
                'query' => [
                    'key' => $this->geminiApiKey,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => $maxTokens,
                        'responseMimeType' => 'application/json',
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $system."\n\n".$user],
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $payload = $response->toArray(false);
            $raw = (string) ($payload['candidates'][0]['content']['parts'][0]['text'] ?? '');

            return $this->decodeJsonText($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function callClaude(string $system, string $user, int $maxTokens = 220): ?array
    {
        try {
            $response = $this->httpClient->request('POST', self::CLAUDE_ENDPOINT, [
                'timeout' => 12,
                'headers' => [
                    'x-api-key' => $this->claudeApiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->claudeModel,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.3,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $user,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return null;
            }

            $payload = $response->toArray(false);
            $raw = (string) ($payload['content'][0]['text'] ?? '');

            return $this->decodeJsonText($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function decodeJsonText(string $raw): ?array
    {
        $text = trim($raw);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function getProvider(): string
    {
        $provider = strtolower(trim($this->provider));

        return in_array($provider, ['openai', 'gemini', 'claude'], true) ? $provider : 'openai';
    }

    private function ruleBasedDuration(int $priority): int
    {
        return match ($priority) {
            3 => 50,
            2 => 35,
            default => 25,
        };
    }
}
