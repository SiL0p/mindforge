<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class UserController extends AbstractController
{
    // Home page
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('user/index.html.twig');
    }

    // Public landing page (not login)
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig');
    }

    // ✅ Real Login page
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // ✅ Signup page
    #[Route('/signup', name: 'app_signup')]
    public function signup(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $em
    ): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);

            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Account created successfully!');

            return $this->redirectToRoute('app_user');
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
}
