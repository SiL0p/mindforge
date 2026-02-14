<?php

namespace App\Controller\Analyst\Admin;

use App\Entity\Analyst\Badge;
use App\Entity\Analyst\GamificationStats;
use App\Entity\Analyst\UserBadge;
use App\Service\AnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AnalystAdminController extends AbstractController
{
    #[Route('/admin/analytics', name: 'admin_analytics')]
    public function index(AnalyticsService $analytics): Response
    {
        $userStats = $analytics->getUserStats();
        $activityStats = $analytics->getActivityStats();
        $locales = $analytics->getLocales();
        $timezones = $analytics->getTimezones();
        $recentUsers = $analytics->getRecentActiveUsers();

        return $this->render('admin/analytics.html.twig', [
            'userStats' => $userStats,
            'activityStats' => $activityStats,
            'locales' => $locales,
            'timezones' => $timezones,
            'recentUsers' => $recentUsers,
        ]);
    }

    #[Route('/admin/analyst', name: 'admin_analyst_overview')]
    #[IsGranted('ROLE_ADMIN')]
    public function analystOverview(EntityManagerInterface $em): Response
    {
        $badgeRepo = $em->getRepository(Badge::class);
        $userBadgeRepo = $em->getRepository(UserBadge::class);
        $statsRepo = $em->getRepository(GamificationStats::class);

        $badgeCount = $badgeRepo->count([]);
        $userBadgeCount = $userBadgeRepo->count([]);
        $statsCount = $statsRepo->count([]);

        $topUsers = $statsRepo->findBy([], ['totalXp' => 'DESC'], 10);
        $recentUnlocks = $userBadgeRepo->findBy([], ['earnedAt' => 'DESC'], 10);

        return $this->render('admin/analyst/overview.html.twig', [
            'badgeCount' => $badgeCount,
            'userBadgeCount' => $userBadgeCount,
            'statsCount' => $statsCount,
            'topUsers' => $topUsers,
            'recentUnlocks' => $recentUnlocks,
        ]);
    }
}
