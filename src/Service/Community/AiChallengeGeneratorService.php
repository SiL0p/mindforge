<?php

namespace App\Service\Community;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiChallengeGeneratorService
{
    private const API_BASE_URL = 'https://api.groq.com/openai/v1';
    // Using llama-3.1 instant which is a stable lightweight model
    private const MODEL = 'llama-3.1-8b-instant';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {
        // Validate API key format
        if (empty($this->groqApiKey)) {
            throw new \Exception('GROQ_API_KEY environment variable is not set');
        }
    }

    /**
     * Generate challenge data using AI based on selected category
     */
    public function generateChallenge(string $category): array
    {
        try {
            $prompt = $this->buildPrompt($category);

            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ],
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $responseBody = $response->getContent(false);
                throw new \Exception('API returned status code: ' . $statusCode . ' - Response: ' . $responseBody);
            }

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid API response format: ' . json_encode($data));
            }

            $content = $data['choices'][0]['message']['content'];

            return $this->parseGeneratedContent($content);
        } catch (\Exception $e) {
            // Return a fallback challenge if API fails
            return $this->getFallbackChallenge($category);
        }
    }

    /**
     * Build the prompt for AI based on category
     */
    private function buildPrompt(string $category): string
    {
        $categoryDescriptions = [
            'tech_skills' => 'a technical skills challenge (programming, databases, etc.)',
            'soft_skills' => 'a soft skills challenge (communication, leadership, etc.)',
            'physical' => 'a physical challenge (sports, health, wellness)',
            'creative' => 'a creative challenge (art, design, music, writing)',
        ];

        $description = $categoryDescriptions[$category] ?? 'a challenge';

        return <<<PROMPT
Generate a fun and motivating $description in exactly this JSON format:
{
  "title": "Challenge title (2-5 words maximum)",
  "description": "Detailed description of the challenge (2-3 sentences)",
  "difficulty": "easy"
}

Respond ONLY with valid JSON, no additional text.
PROMPT;
    }

    /**
     * Parse the AI response and extract title, description, difficulty
     */
    private function parseGeneratedContent(string $content): array
    {
        // Trim the content first
        $content = trim($content);
        
        // Extract JSON from the response (handles cases where there's extra text)
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonStr = $matches[0];
        } else {
            throw new \Exception('Could not extract JSON from AI response: ' . substr($content, 0, 200));
        }

        $data = json_decode($jsonStr, true);

        if (!$data || !is_array($data)) {
            throw new \Exception('Invalid JSON in AI response: ' . $jsonStr);
        }

        // Validate required fields
        if (!isset($data['title']) || !isset($data['description'])) {
            throw new \Exception('Missing required fields (title/description) in AI response: ' . json_encode($data));
        }

        // Default difficulty to medium if not provided or invalid
        $difficulty = $data['difficulty'] ?? 'medium';
        $validDifficulties = ['easy', 'medium', 'hard'];
        if (!in_array($difficulty, $validDifficulties, true)) {
            $difficulty = 'medium';
        }

        return [
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'difficulty' => $difficulty,
        ];
    }

    /**
     * Get fallback challenge examples when API fails
     */
    private function getFallbackChallenge(string $category): array
    {
        $fallbacks = [
            'tech_skills' => [
                'title' => 'Build a REST API',
                'description' => 'Create a functional REST API with at least 5 endpoints that handle CRUD operations. Document your API with clear examples.',
                'difficulty' => 'medium',
            ],
            'soft_skills' => [
                'title' => 'Lead a Team Meeting',
                'description' => 'Organize and lead a productive team meeting. Focus on clear communication and decision making.',
                'difficulty' => 'medium',
            ],
            'physical' => [
                'title' => 'Run 5K in a Week',
                'description' => 'Complete a 5-kilometer run or walk/run combination. Track your time and try to improve it.',
                'difficulty' => 'easy',
            ],
            'creative' => [
                'title' => 'Write a Short Story',
                'description' => 'Write a 1000+ word short story with a compelling plot and character development.',
                'difficulty' => 'medium',
            ],
        ];

        // Return the example for the category or use tech_skills as default
        return $fallbacks[$category] ?? $fallbacks['tech_skills'];
    }
}
