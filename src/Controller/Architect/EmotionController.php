<?php
namespace App\Controller\Architect;

use App\Entity\Architect\EmotionLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmotionController extends AbstractController
{
    #[Route('/emotion', name: 'app_emotion')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('emotion/index.html.twig');
    }

    #[Route('/emotion/save', name: 'app_emotion_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Not logged in'], 401);
        }

        $data       = json_decode($request->getContent(), true);
        $emotion    = $data['emotion']    ?? null;
        $confidence = $data['confidence'] ?? 0;

        if (!$emotion) {
            return new JsonResponse(['success' => false, 'error' => 'No emotion provided'], 400);
        }

        $log = new EmotionLog($emotion, (float)$confidence, $user);
        $em->persist($log);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/emotion/groq', name: 'app_emotion_groq', methods: ['POST'])]
    public function groq(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Not logged in'], 401);
        }

        $data   = json_decode($request->getContent(), true);
        $apiKey = trim(preg_replace('/\s+/', '', $this->getParameter('groq_api_key')));
        $isChat = !empty($data['chat']);

        if ($isChat) {
            // ── Chat mode ──
            $messages = $data['messages'] ?? [];
        } else {
            // ── Insight mode ──
            $emotion  = preg_replace('/[^a-zA-Z]/', '', $data['emotion'] ?? 'neutral');
            $messages = [
                [
                    'role'    => 'system',
                    'content' => 'You are a compassionate mental health AI. Always respond with exactly 3 sections labeled: FEELING, SONG, EXERCISE.'
                ],
                [
                    'role'    => 'user',
                    'content' => "My detected emotion is: {$emotion}.
    Please provide:
    FEELING: A warm, empathetic explanation of what this emotion means and that it is valid (2-3 sentences).
    SONG: One calming or uplifting song recommendation with artist name and one sentence explaining why it helps.
    EXERCISE: One simple mental health exercise or breathing technique I can do right now to feel better (3-4 steps)."
                ]
            ];
        }

        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => $messages,
            'max_tokens'  => 600,
            'temperature' => 0.7,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode !== 200) {
            $decoded = json_decode($response, true);
            return new JsonResponse([
                'success' => false,
                'error'   => $decoded['error']['message'] ?? 'HTTP ' . $httpCode,
            ]);
        }

        $result  = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';

        return new JsonResponse(['success' => true, 'content' => $content]);
    }
    #[Route('/emotion/history', name: 'app_emotion_history')]
    public function history(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $logs = $em->getRepository(EmotionLog::class)->findBy(
            ['user' => $user],
            ['detectedAt' => 'DESC'],
            20
        );

        return $this->render('emotion/history.html.twig', ['logs' => $logs]);
    }
}
