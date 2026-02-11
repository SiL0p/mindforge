<?php
namespace App\Controller\Architect;

use App\Entity\Architect\Profile;
use App\Entity\Architect\User;
use App\Form\Architect\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        // Get the currently authenticated user
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get or create profile for the current user
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $em->persist($profile);
        }
        
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatarFile')->getData();
            
            if ($avatarFile) {
                // Delete old avatar if exists
                if ($profile->getAvatar()) {
                    $oldAvatarPath = $this->getParameter('avatars_directory') . '/' . $profile->getAvatar();
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();
                
                $avatarFile->move(
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );
                
                $profile->setAvatar($newFilename);
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile_edit');
        }
        
        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
            'user' => $user,
        ]);
    }
    #[Route('/profile', name: 'app_profile_view')]
public function view(EntityManagerInterface $em): Response
{
    // Get the currently authenticated user
    $user = $this->getUser();
    
    // Redirect to login if not authenticated
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    // Get profile
    $profile = $user->getProfile();
    if (!$profile) {
        // If no profile exists, redirect to edit to create one
        $this->addFlash('info', 'Please complete your profile.');
        return $this->redirectToRoute('app_profile_edit');
    }
    
    return $this->render('user/profile.html.twig', [
        'user' => $user,
        'profile' => $profile,
    ]);
}
}