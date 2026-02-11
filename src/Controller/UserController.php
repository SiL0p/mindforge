<?php
namespace App\Controller;

use App\Entity\Architect\User;
use App\Entity\Architect\Profile;  
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
    public function login(
        AuthenticationUtils $authenticationUtils
    ): Response {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
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

    // Blog page
    #[Route('/user/blog', name: 'app_blog')]
    public function blog(): Response
    {
        return $this->render('user/blog.html.twig');
    }

    // Blog post page
    #[Route('/user/blog-post', name: 'app_blog_post')]
    public function blogPost(): Response
    {
        return $this->render('user/blog-post.html.twig');
    }

    // Logout route (Symfony intercepts this)
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
    // Get all users
    $users = $em->getRepository(User::class)->findAll();
    
    // If no users, use a dummy for testing
    if (empty($users)) {
        $user = null;
    } else {
        // Use first user
        $user = $users[0];
    }
    
    // Handle form submission
    if ($request->isMethod('POST') && $user) {
        $motivation = $request->request->get('motivation');
        
        if (!empty($motivation) && strlen($motivation) >= 50) {
            $roleRequest = new RoleRequest();
            $roleRequest->setUser($user);
            $roleRequest->setMotivation($motivation);
            
            $em->persist($roleRequest);
            $em->flush();
            
            $this->addFlash('success', 'Request submitted successfully!');
        } else {
            $this->addFlash('error', 'Motivation must be at least 50 characters.');
        }
    }
    
    return $this->render('user/request_student_plus.html.twig', [
        'user' => $user,
        'all_users' => $users,
    ]);
}

#[Route('/my-request-status', name: 'app_request_status')]
public function requestStatus(EntityManagerInterface $em): Response
{
    $users = $em->getRepository(User::class)->findAll();
    $user = empty($users) ? null : $users[0];
    
    $requests = [];
    if ($user) {
        $requests = $em->getRepository(RoleRequest::class)->findBy(
            ['user' => $user],
            ['requestedAt' => 'DESC']
        );
    }
    
    return $this->render('user/request_status.html.twig', [
        'requests' => $requests,
        'user' => $user,
    ]);
}}