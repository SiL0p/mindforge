<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Main dashboard stats (REAL)
     */
    public function getUserStats(): array
    {
        $conn = $this->em->getConnection();

        // Total users
        $totalUsers = (int) $conn->fetchOne('SELECT COUNT(*) FROM user');

        // Verified users
        $verifiedUsers = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM user WHERE is_verified = 1'
        );

        // New users today
        $newToday = (int) $conn->fetchOne("
            SELECT COUNT(*) 
            FROM user 
            WHERE DATE(created_at) = CURDATE()
        ");

        // New users this week
        $newWeek = (int) $conn->fetchOne("
            SELECT COUNT(*) 
            FROM user 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");

        // Users with profile
        $usersWithProfile = (int) $conn->fetchOne("
            SELECT COUNT(*) 
            FROM profile
        ");

        // Users with avatar
        $usersWithAvatar = (int) $conn->fetchOne("
            SELECT COUNT(*) 
            FROM profile
            WHERE avatar IS NOT NULL AND avatar != ''
        ");

        // Users with bio
        $usersWithBio = (int) $conn->fetchOne("
            SELECT COUNT(*) 
            FROM profile
            WHERE bio IS NOT NULL AND bio != ''
        ");

        return [
            'total_users'        => $totalUsers,
            'verified_users'     => $verifiedUsers,
            'new_today'          => $newToday,
            'new_week'           => $newWeek,
            'users_with_profile' => $usersWithProfile,
            'users_with_avatar'  => $usersWithAvatar,
            'users_with_bio'     => $usersWithBio,
        ];
    }

    /**
     * Active users based on focus sessions (REAL activity)
     */
    public function getActivityStats(): array
    {
        $conn = $this->em->getConnection();

        // Active today (users who had a focus session today)
        $activeToday = (int) $conn->fetchOne("
            SELECT COUNT(DISTINCT user_id)
            FROM focus_session
            WHERE DATE(started_at) = CURDATE()
        ");

        // Active this week
        $activeWeek = (int) $conn->fetchOne("
            SELECT COUNT(DISTINCT user_id)
            FROM focus_session
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");

        // Average focus duration (minutes)
        $avgFocus = (int) $conn->fetchOne("
            SELECT AVG(duration)
            FROM focus_session
        ");

        // Total focus time (all users)
        $totalFocus = (int) $conn->fetchOne("
            SELECT SUM(duration)
            FROM focus_session
        ");

        return [
            'active_today'   => $activeToday,
            'active_week'    => $activeWeek,
            'avg_focus_time' => round($avgFocus),
            'total_focus'    => $totalFocus,
        ];
    }

    /**
     * Profile locales distribution
     */
    public function getLocales(): array
    {
        $conn = $this->em->getConnection();

        return $conn->fetchAllKeyValue("
            SELECT locale, COUNT(*) as cnt
            FROM profile
            GROUP BY locale
            ORDER BY cnt DESC
        ");
    }

    /**
     * Timezones distribution
     */
    public function getTimezones(): array
    {
        $conn = $this->em->getConnection();

        return $conn->fetchAllKeyValue("
            SELECT timezone, COUNT(*) as cnt
            FROM profile
            GROUP BY timezone
            ORDER BY cnt DESC
        ");
    }

    /**
     * Recent active users (REAL)
     */
   public function getRecentActiveUsers(int $limit = 10): array
{
    $conn = $this->em->getConnection();
    $limit = (int) $limit; // HARD CAST (safe)

    $sql = "
        SELECT 
            u.id,
            u.email,
            u.created_at,
            p.first_name,
            p.last_name,
            p.avatar,
            MAX(fs.started_at) as last_focus,
            COUNT(fs.id) as focus_sessions,
            SUM(fs.duration) as total_focus_time
        FROM user u
        LEFT JOIN profile p ON p.user_id = u.id
        LEFT JOIN focus_session fs ON fs.user_id = u.id
        GROUP BY u.id
        ORDER BY last_focus DESC
        LIMIT $limit
    ";

    return $conn->fetchAllAssociative($sql);
}

}
