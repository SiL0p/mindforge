<?php
namespace App\Controller;

use App\Entity\Analyst\GamificationStats;
use App\Entity\Architect\User;
use App\Entity\Architect\Profile;  
use App\Entity\Community\Claim;
use App\Entity\Community\SharedTask;
use App\Entity\Planner\Exam;
use App\Entity\Planner\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\Architect\RoleRequest;

final class UserController extends AbstractController
{
    // Home page
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('user/index.html.twig');
    }

    // Public landing page
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workspace');
        }

        return $this->render('user/login.html.twig');
    }

    #[Route('/login/student', name: 'app_login_student')]
    public function loginStudent(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workspace');
        }

        return $this->render('user/login_student.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/company', name: 'app_login_company')]
    public function loginCompany(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workspace');
        }

        return $this->render('user/login_company.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/workspace', name: 'app_workspace')]
    public function workspace(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $taskRepo = $em->getRepository(Task::class);
        $claimRepo = $em->getRepository(Claim::class);
        $challengeRepo = $em->getRepository(SharedTask::class);
        $examRepo = $em->getRepository(Exam::class);
        $statsRepo = $em->getRepository(GamificationStats::class);

        $myTaskCount = $taskRepo->count(['owner' => $user]);
        $myDoneTaskCount = $taskRepo->count(['owner' => $user, 'status' => Task::STATUS_DONE]);
        $myClaimCount = $claimRepo->count(['createdBy' => $user]);
        $myOpenClaimCount = $claimRepo->count(['createdBy' => $user, 'status' => 'open']);
        $myReceivedChallenges = $challengeRepo->count(['sharedWith' => $user]);
        $mySentChallenges = $challengeRepo->count(['sharedBy' => $user]);
        $myExamCount = $examRepo->count(['owner' => $user]);

        $recentTasks = $taskRepo->findBy(['owner' => $user], ['createdAt' => 'DESC'], 5);
        $recentChallenges = $challengeRepo->findBy(['sharedWith' => $user], ['createdAt' => 'DESC'], 5);
        $recentClaims = $claimRepo->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 5);

        $myStats = $statsRepo->findOneBy(['user' => $user]);

        return $this->render('user/workspace.html.twig', [
            'myTaskCount' => $myTaskCount,
            'myDoneTaskCount' => $myDoneTaskCount,
            'myClaimCount' => $myClaimCount,
            'myOpenClaimCount' => $myOpenClaimCount,
            'myReceivedChallenges' => $myReceivedChallenges,
            'mySentChallenges' => $mySentChallenges,
            'myExamCount' => $myExamCount,
            'myXp' => $myStats?->getTotalXp() ?? 0,
            'myLevel' => $myStats?->getCurrentLevel() ?? 1,
            'recentTasks' => $recentTasks,
            'recentChallenges' => $recentChallenges,
            'recentClaims' => $recentClaims,
        ]);
    }
    #[Route('/signup', name: 'app_signup')]
    public function signup(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // ✅ Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                $this->addFlash('error', 'Email and password are required.');
                return $this->render('user/signup.html.twig');
            }
            
            // ✅ Check if email already exists
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                $this->addFlash('error', 'This email is already registered.');
                return $this->render('user/signup.html.twig');
            }
            
            try {
                // 1️⃣ Create user
                $user = new User();
                $user->setEmail($data['email']);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
                // Don't set username since it doesn't exist in your schema
                
                // 2️⃣ Create profile
                $profile = new Profile();
                $profile->setUser($user);
                $profile->setFirstName($data['first_name'] ?? null);
                $profile->setLastName($data['last_name'] ?? null);
                
                // 3️⃣ Persist everything
                $em->persist($user);
                $em->persist($profile);
                $em->flush();
                
                $this->addFlash('success', 'Account created successfully. Please login.');
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred. Please try again.');
                // Log the error: $this->logger->error($e->getMessage());
            }
        }
        
        return $this->render('user/signup.html.twig');
    }

    // Contact page
    #[Route('/user/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('user/contact.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by Symfony.');
    }

    #[Route('/request-student-plus', name: 'app_request_student_plus')]
public function requestStudentPlus(
    Request $request,
    EntityManagerInterface $em
): Response {
    // Get the currently logged-in user
    $user = $this->getUser();
    
    // If no user is logged in, redirect to login
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    // Check if user already has Student+ or Admin role
    if ($user->getRoles() && (in_array('ROLE_STUDENT_PLUS', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()))) {
        $this->addFlash('info', 'You already have Student+ or Admin status!');
        return $this->redirectToRoute('app_home');
    }
    
    // Check if user already has a pending request
    $existingRequest = $em->getRepository(RoleRequest::class)->findOneBy([
        'user' => $user,
        'status' => 'pending' // Assuming you have a status field
    ]);
    
    if ($existingRequest) {
        $this->addFlash('warning', 'You already have a pending request. Please wait for admin approval.');
        return $this->redirectToRoute('app_request_status');
    }
    
    // Handle form submission
    if ($request->isMethod('POST')) {
        $motivation = $request->request->get('motivation');
        
        if (!empty($motivation) && strlen($motivation) >= 50) {
            $roleRequest = new RoleRequest();
            $roleRequest->setUser($user);
            $roleRequest->setMotivation($motivation);
            // Set default status to pending
            $roleRequest->setStatus('pending');
            
            $em->persist($roleRequest);
            $em->flush();
            
            $this->addFlash('success', 'Request submitted successfully! We will review it soon.');
            
            // Redirect to avoid form resubmission
            return $this->redirectToRoute('app_request_status');
        } else {
            $this->addFlash('error', 'Motivation must be at least 50 characters.');
        }
    }
    
    return $this->render('user/request_student_plus.html.twig', [
        'user' => $user,
    ]);
}

#[Route('/my-request-status', name: 'app_request_status')]
public function requestStatus(EntityManagerInterface $em): Response
{
    // Get the currently logged-in user
    $user = $this->getUser();
    
    // If no user is logged in, redirect to login
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    $requests = $em->getRepository(RoleRequest::class)->findBy(
        ['user' => $user],
        ['requestedAt' => 'DESC']
    );
    
    return $this->render('user/request_status.html.twig', [
        'requests' => $requests,
        'user' => $user,
    ]);
}
}