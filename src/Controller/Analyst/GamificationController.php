<?php

namespace App\Controller\Analyst;

use App\Entity\Architect\User;
use App\Repository\Analyst\GamificationStatsRepository;
use App\Service\Analyst\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analyst')]
#[IsGranted('ROLE_USER')]
class GamificationController extends AbstractController
{
    #[Route('/gamification', name: 'app_analyst_gamification', methods: ['GET'])]
    public function index(GamificationService $gamificationService): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $data = $gamificationService->getDashboardData($user);

        return $this->render('analyst/gamification/index.html.twig', [
            'stats' => $data['stats'],
            'badges' => $data['badges'],
        ]);
    }

    #[Route('/leaderboard', name: 'app_analyst_leaderboard', methods: ['GET'])]
    public function leaderboard(GamificationStatsRepository $statsRepository): Response
    {
        $leaders = $statsRepository->findTopLeaderboard();

        return $this->render('analyst/gamification/leaderboard.html.twig', [
            'leaders' => $leaders,
        ]);
    }
}
