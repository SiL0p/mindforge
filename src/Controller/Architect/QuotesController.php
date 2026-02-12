<?php

namespace App\Controller\Architect;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuotesController extends AbstractController
{

#[Route('/quotes', name: 'app_quotes')]
public function index(HttpClientInterface $httpClient): Response
{
    try {
        // Switching to ZenQuotes (No API key needed for basic use)
        $response = $httpClient->request('GET', 'https://zenquotes.io/api/random');

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            // ZenQuotes returns an array of objects, so we take index 0
            $quote = $data[0]['q'] ?? 'Learning is a journey.';
            $author = $data[0]['a'] ?? 'Edusite';
        }
    } catch (\Exception $e) {
        // This will help you see the error in your Symfony Profiler/Logs
        $this->addFlash('error', 'API Error: ' . $e->getMessage());
        $quote = 'Keep pushing forward!';
        $author = 'System';
    }

    return $this->render('user/quotes.html.twig', [
        'quote' => $quote,
        'author' => $author,
    ]);
}
}