<?php

declare(strict_types=1);

namespace App\Service\Guardian;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalLearningResourceService
{
    private const OPEN_LIBRARY_SEARCH_ENDPOINT = 'https://openlibrary.org/search.json';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function fetchOpenLibrarySuggestions(string $query, int $limit = 6): array
    {
        $safeQuery = trim($query);
        $safeLimit = max(1, min(10, $limit));

        if ($safeQuery === '') {
            $safeQuery = 'study skills';
        }

        try {
            $response = $this->httpClient->request('GET', self::OPEN_LIBRARY_SEARCH_ENDPOINT, [
                'timeout' => 6,
                'query' => [
                    'q' => $safeQuery,
                    'limit' => $safeLimit,
                    'language' => 'eng',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $payload = $response->toArray(false);
            $docs = $payload['docs'] ?? [];
            if (!is_array($docs)) {
                return [];
            }

            $results = [];
            foreach ($docs as $doc) {
                if (!is_array($doc)) {
                    continue;
                }

                $title = trim((string) ($doc['title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $author = 'Unknown author';
                if (!empty($doc['author_name']) && is_array($doc['author_name'])) {
                    $author = (string) ($doc['author_name'][0] ?? $author);
                }

                $year = null;
                if (isset($doc['first_publish_year'])) {
                    $year = (int) $doc['first_publish_year'];
                }

                $workKey = trim((string) ($doc['key'] ?? ''));
                $url = $workKey !== '' ? 'https://openlibrary.org'.$workKey : 'https://openlibrary.org';

                $results[] = [
                    'title' => $title,
                    'author' => $author,
                    'year' => $year,
                    'url' => $url,
                ];

                if (count($results) >= $safeLimit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable) {
            return [];
        }
    }
}
