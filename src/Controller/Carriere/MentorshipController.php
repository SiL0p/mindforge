<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\Mentorship;
use App\Form\Carriere\MentorshipNoteType;
use App\Form\Carriere\MentorshipType;
use App\Repository\Carriere\MentorshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/mentorship')]
class MentorshipController extends AbstractController
{
    #[Route('', name: 'app_carriere_mentorship_index')]
    #[IsGranted('ROLE_USER')]
    public function index(MentorshipRepository $mentorshipRepository): Response
    {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Find mentorships where user is student
        $mentorshipsAsStudent = $mentorshipRepository->findByStudentEmail($userEmail);

        // Find mentorships where user is mentor
        $mentorshipsAsMentor = $mentorshipRepository->findByMentorEmail($userEmail);

        return $this->render('carriere/mentorship/index.html.twig', [
            'mentorships_as_student' => $mentorshipsAsStudent,
            'mentorships_as_mentor' => $mentorshipsAsMentor,
        ]);
    }

    #[Route('/new', name: 'app_carriere_mentorship_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        MentorshipRepository $mentorshipRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        $mentorship = new Mentorship();
        $mentorship->setStudentEmail($userEmail);

        $form = $this->createForm(MentorshipType::class, $mentorship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if mentor email is different from student email
            if ($mentorship->getMentorEmail() === $userEmail) {
                $this->addFlash('error', 'You cannot request mentorship from yourself.');
                return $this->redirectToRoute('app_carriere_mentorship_new');
            }

            // Check if active mentorship already exists between student and mentor
            if ($mentorshipRepository->hasActiveMentorship($userEmail, $mentorship->getMentorEmail())) {
                $this->addFlash('warning', 'You already have an active mentorship request with this mentor.');
                return $this->redirectToRoute('app_carriere_mentorship_index');
            }

            $entityManager->persist($mentorship);
            $entityManager->flush();

            $this->addFlash('success', 'Mentorship request has been sent successfully!');

            return $this->redirectToRoute('app_carriere_mentorship_index');
        }

        return $this->render('carriere/mentorship/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carriere_mentorship_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        Mentorship $mentorship,
        Request $request
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Check if the logged-in user is a participant
        if (!$mentorship->isParticipant($userEmail)) {
            throw $this->createAccessDeniedException('You can only view mentorships you are participating in.');
        }

        // Create note form if mentorship is active
        $noteForm = null;
        if ($mentorship->isActive()) {
            $noteForm = $this->createForm(MentorshipNoteType::class);
        }

        return $this->render('carriere/mentorship/show.html.twig', [
            'mentorship' => $mentorship,
            'note_form' => $noteForm?->createView(),
        ]);
    }

    #[Route('/{id}/note', name: 'app_carriere_mentorship_add_note', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addNote(
        Request $request,
        Mentorship $mentorship,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Check if the logged-in user is a participant
        if (!$mentorship->isParticipant($userEmail)) {
            throw $this->createAccessDeniedException('You can only add notes to mentorships you are participating in.');
        }

        // Check if mentorship is active
        if (!$mentorship->isActive()) {
            $this->addFlash('error', 'You can only add notes to active mentorships.');
            return $this->redirectToRoute('app_carriere_mentorship_show', ['id' => $mentorship->getId()]);
        }

        $form = $this->createForm(MentorshipNoteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->get('content')->getData();
            $mentorship->addNote($userEmail, $content);

            $entityManager->flush();

            $this->addFlash('success', 'Note added successfully!');
        } else {
            $this->addFlash('error', 'Failed to add note. Please check your input.');
        }

        return $this->redirectToRoute('app_carriere_mentorship_show', ['id' => $mentorship->getId()]);
    }

    #[Route('/{id}/accept', name: 'app_carriere_mentorship_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function accept(
        Request $request,
        Mentorship $mentorship,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Check if the logged-in user is the mentor
        if (!$mentorship->isMentor($userEmail)) {
            throw $this->createAccessDeniedException('Only the mentor can accept a mentorship request.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('accept'.$mentorship->getId(), $request->request->get('_token'))) {
            if ($mentorship->isPending()) {
                try {
                    $mentorship->accept();
                    $entityManager->flush();

                    $this->addFlash('success', 'Mentorship request accepted successfully!');
                } catch (\LogicException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Only pending mentorship requests can be accepted.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_carriere_mentorship_show', ['id' => $mentorship->getId()]);
    }

    #[Route('/{id}/complete', name: 'app_carriere_mentorship_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function complete(
        Request $request,
        Mentorship $mentorship,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Check if the logged-in user is a participant
        if (!$mentorship->isParticipant($userEmail)) {
            throw $this->createAccessDeniedException('You can only complete mentorships you are participating in.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('complete'.$mentorship->getId(), $request->request->get('_token'))) {
            if ($mentorship->isActive()) {
                try {
                    $mentorship->complete();
                    $entityManager->flush();

                    $this->addFlash('success', 'Mentorship completed successfully!');
                } catch (\LogicException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Only active mentorships can be completed.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_carriere_mentorship_show', ['id' => $mentorship->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_carriere_mentorship_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(
        Request $request,
        Mentorship $mentorship,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $userEmail = $user->getUserIdentifier();

        // Check if the logged-in user is a participant
        if (!$mentorship->isParticipant($userEmail)) {
            throw $this->createAccessDeniedException('You can only cancel mentorships you are participating in.');
        }

        // Check CSRF token
        if ($this->isCsrfTokenValid('cancel'.$mentorship->getId(), $request->request->get('_token'))) {
            if ($mentorship->isPending() || $mentorship->isActive()) {
                try {
                    $mentorship->cancel();
                    $entityManager->flush();

                    $this->addFlash('success', 'Mentorship cancelled successfully.');
                } catch (\LogicException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Only pending or active mentorships can be cancelled.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_carriere_mentorship_index');
    }
}
