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
        // -------------------------------
        // TEMP: use first user for testing
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            throw new \Exception('No user found with ID 1. Please create a user in the DB.');
        }
        // -------------------------------

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
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$avatarFile->guessExtension();

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
        ]);
    }
}
