<?php
namespace App\Controller;
use App\Service\AnalyticsService;
use App\Entity\Architect\User;
use App\Entity\Analyst\Badge;
use App\Entity\Analyst\GamificationStats;
use App\Entity\Analyst\UserBadge;
use App\Entity\Planner\Exam;
use App\Entity\Planner\Subject;
use App\Entity\Planner\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Architect\RoleRequest; 
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/view/{id}', name: 'admin_users_view')]
    public function viewUser(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        return $this->render('admin/user_view.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/edit/{id}', name: 'admin_users_edit')]
    public function editUser(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Update user data
            $user->setEmail($data['email']);
            $user->setIsVerified(isset($data['is_verified']));
            
            // Update roles
            $roles = $data['roles'] ?? ['ROLE_USER'];
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            $user->setRoles($roles);
            
            // Update password if provided
            if (!empty($data['password'])) {
                $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            }
            
            $em->flush();
            
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_users');
        }
        
        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/delete/{id}', name: 'admin_users_delete')]
    public function deleteUser(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        $em->remove($user);
        $em->flush();
        
        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/tables', name: 'admin_tables')]
    public function tables(): Response
    {
        return $this->render('admin/tables.html.twig');
    }

    #[Route('/admin/charts', name: 'admin_charts')]
    public function charts(): Response
    {
        return $this->render('admin/charts.html.twig');
    }
    #[Route('/admin/role-requests', name: 'admin_role_requests')]
public function roleRequests(EntityManagerInterface $em): Response
{
    // Get all role requests, ordered by newest first
    $requests = $em->getRepository(RoleRequest::class)->findBy(
        [],
        ['requestedAt' => 'DESC']
    );
    
    return $this->render('admin/role_requests.html.twig', [
        'requests' => $requests,
    ]);
}

#[Route('/admin/role-requests/approve/{id}', name: 'admin_role_request_approve')]
public function approveRequest(int $id, EntityManagerInterface $em): Response
{
    $request = $em->getRepository(RoleRequest::class)->find($id);
    
    if (!$request) {
        $this->addFlash('error', 'Request not found.');
        return $this->redirectToRoute('admin_role_requests');
    }
    
    // Update request status
    $request->setStatus('approved');
    $request->setReviewedAt(new \DateTime());
    
    // Update user role to Student+
    $user = $request->getUser();
    $roles = $user->getRoles();
    if (!in_array('ROLE_STUDENT_PLUS', $roles)) {
        $roles[] = 'ROLE_STUDENT_PLUS';
        $user->setRoles($roles);
    }
    
    $em->flush();
    
    $this->addFlash('success', 'Request approved! User now has Student+ role.');
    return $this->redirectToRoute('admin_role_requests');
}

#[Route('/admin/role-requests/reject/{id}', name: 'admin_role_request_reject')]
public function rejectRequest(
    int $id, 
    Request $httpRequest, 
    EntityManagerInterface $em
): Response {
    $request = $em->getRepository(RoleRequest::class)->find($id);
    
    if (!$request) {
        $this->addFlash('error', 'Request not found.');
        return $this->redirectToRoute('admin_role_requests');
    }
    
    // If POST, process rejection
    if ($httpRequest->isMethod('POST')) {
        $adminNotes = $httpRequest->request->get('admin_notes');
        
        $request->setStatus('rejected');
        $request->setReviewedAt(new \DateTime());
        $request->setAdminNotes($adminNotes);
        
        $em->flush();
        
        $this->addFlash('success', 'Request rejected.');
        return $this->redirectToRoute('admin_role_requests');
    }
    
    // Show rejection form
    return $this->render('admin/role_request_reject.html.twig', [
        'request' => $request,
    ]);
}
 #[Route('/admin/analytics', name: 'admin_analytics')]
    public function index(AnalyticsService $analytics)
    {
        $userStats     = $analytics->getUserStats();
        $activityStats = $analytics->getActivityStats();
        $locales       = $analytics->getLocales();
        $timezones     = $analytics->getTimezones();
        $recentUsers   = $analytics->getRecentActiveUsers();

        return $this->render('admin/analytics.html.twig', [
            'userStats'     => $userStats,
            'activityStats' => $activityStats,
            'locales'       => $locales,
            'timezones'     => $timezones,
            'recentUsers'   => $recentUsers,
        ]);
    }

    #[Route('/admin/planner', name: 'admin_planner_overview')]
    #[IsGranted('ROLE_ADMIN')]
    public function plannerOverview(EntityManagerInterface $em): Response
    {
        $taskRepo = $em->getRepository(Task::class);

        $totalTasks = $taskRepo->count([]);
        $todoTasks = $taskRepo->count(['status' => Task::STATUS_TODO]);
        $inProgressTasks = $taskRepo->count(['status' => Task::STATUS_IN_PROGRESS]);
        $doneTasks = $taskRepo->count(['status' => Task::STATUS_DONE]);
        $recentTasks = $taskRepo->findBy([], ['createdAt' => 'DESC'], 10);

        $totalSubjects = $em->getRepository(Subject::class)->count([]);

        $examError = null;
        $totalExams = 0;
        $upcomingExams = [];

        try {
            $examRepo = $em->getRepository(Exam::class);
            $totalExams = $examRepo->count([]);
            $upcomingExams = $examRepo->createQueryBuilder('e')
                ->where('e.examDate >= :now')
                ->setParameter('now', new \DateTimeImmutable())
                ->orderBy('e.examDate', 'ASC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        } catch (\Throwable $throwable) {
            $examError = 'Exam table/mapping is not available in this environment.';
        }

        $completionRate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

        return $this->render('admin/Planner/overview.html.twig', [
            'totalTasks' => $totalTasks,
            'todoTasks' => $todoTasks,
            'inProgressTasks' => $inProgressTasks,
            'doneTasks' => $doneTasks,
            'completionRate' => $completionRate,
            'totalSubjects' => $totalSubjects,
            'totalExams' => $totalExams,
            'upcomingExams' => $upcomingExams,
            'recentTasks' => $recentTasks,
            'examError' => $examError,
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