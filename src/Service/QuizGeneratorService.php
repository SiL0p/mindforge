<?php

namespace App\Service;

use App\Entity\Carriere\OpportuniteCarriere;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuizGeneratorService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENAI_API_KEY')]
        private string $openAiKey,
    ) {}

    /**
     * Generate 5 MCQ questions for a job posting.
     *
     * @return array{question: string, options: string[], correct: int}[]
     *
     * @throws \RuntimeException
     */
    public function generate(OpportuniteCarriere $opp): array
    {
        $prompt = sprintf(
            "You are a professional recruiter. Generate exactly 5 multiple-choice quiz questions to test a candidate's fit and knowledge for the following job posting.\n\n" .
            "Job Title: %s\nJob Type: %s\nDescription: %s\n\n" .
            "Return ONLY a valid JSON array (no markdown, no explanation) with exactly 5 objects, each having:\n" .
            "- \"question\": the question text\n" .
            "- \"options\": an array of exactly 4 strings, each prefixed with \"A. \", \"B. \", \"C. \", \"D. \"\n" .
            "- \"correct\": the 0-based index of the correct option\n\n" .
            "Example: [{\"question\": \"...\", \"options\": [\"A. ...\", \"B. ...\", \"C. ...\", \"D. ...\"], \"correct\": 2}]",
            $opp->getTitle(),
            $opp->getType(),
            mb_substr($opp->getDescription() ?? '', 0, 1500)
        );

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Strip potential markdown code fences
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/', '', $content);

            $questions = json_decode(trim($content), true);

            if (!is_array($questions) || count($questions) !== 5) {
                throw new \RuntimeException('OpenAI returned an unexpected response format.');
            }

            return $questions;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate quiz questions: ' . $e->getMessage(), 0, $e);
        }
    }
}
