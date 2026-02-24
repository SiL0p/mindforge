<?php
// src/Controller/ProfileController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/avatar-preview', name: 'avatar_preview')]
    public function avatarPreview(HttpClientInterface $client, Request $request): Response
    {
        $seed = $request->query->get('seed', 'user');
        $top = $request->query->get('top', 'shortHairShortFlat');
        $hairColor = $request->query->get('hairColor', 'brown');
        $skinColor = $request->query->get('skinColor', 'light');

        $url = "https://avatars.dicebear.com/api/avataaars/{$seed}.svg?top={$top}&hairColor={$hairColor}&skinColor={$skinColor}";

        // Fetch SVG from DiceBear
        $response = $client->request('GET', $url);
        $svg = $response->getContent();

        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    #[Route('/avatar/save', name: 'avatar_save', methods: ['POST'])]
    public function saveAvatar(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $avatar = $data['avatar'] ?? '';

        // For demo: just save to /public/avatars/avatar.svg
        if ($avatar) {
            $path = $this->getParameter('kernel.project_dir') . '/public/avatars/avatar.svg';
            file_put_contents($path, file_get_contents($avatar));
        }

        return $this->json(['status' => 'saved']);
    }
}
