<?php

namespace App\Controller\Community;

use App\Entity\Community\ChatMessage;
use App\Entity\Community\Claim;
use App\Entity\Community\SharedTask;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Architect\User;
use App\Repository\Community\ChatMessageRepository;
use App\Repository\Community\ClaimRepository;
use App\Repository\Community\SharedTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/community')]
class CommunityController extends AbstractController
{
    // ============================================================================
    // COMMUNITY HUB (Index/Dashboard)
    // ============================================================================

    #[Route('', name: 'community_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('community/index.html.twig');
    }

    // ============================================================================
    // CHAT MESSAGE ROUTES (Real-time Chat inside Guardian Virtual Rooms)
    // ============================================================================

    #[Route('/room/{id<\d+>}/message/send', name: 'community_message_send', methods: ['POST'])]
    public function sendMessage(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $virtualRoom = $em->getRepository(VirtualRoom::class)->find($id);
        $user = $this->getUser();

        if (!$virtualRoom) {
            $this->addFlash('error', 'Salle virtuelle introuvable.');
            return $this->redirectToRoute('guardian_rooms');
        }

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$virtualRoom->isParticipant($user) && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous devez rejoindre la salle pour envoyer un message.');
            return $this->redirectToRoute('guardian_room_detail', ['id' => $virtualRoom->getId()]);
        }

        $content = trim($request->request->get('content', ''));

        $chatMessage = new ChatMessage();
        $chatMessage->setContent($content);
        $chatMessage->setSender($user);
        $chatMessage->setVirtualRoom($virtualRoom);

        $errors = $validator->validate($chatMessage);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', implode(' ', $errorMessages));
        } else {
            $em->persist($chatMessage);
            $em->flush();
            $this->addFlash('success', 'Message envoye avec succes.');
        }

        return $this->redirectToRoute('guardian_room_detail', ['id' => $id]);
    }

    #[Route('/message/{id<\d+>}/edit', name: 'community_message_edit', methods: ['POST'])]
    public function editMessage(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $chatMessage = $em->getRepository(ChatMessage::class)->find($id);
        $user = $this->getUser();

        if (!$chatMessage) {
            $this->addFlash('error', 'Message introuvable.');
            return $this->redirectToRoute('guardian_rooms');
        }

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($chatMessage->getSender() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier ce message.');
            return $this->redirectToRoute('guardian_room_detail', ['id' => $chatMessage->getVirtualRoom()->getId()]);
        }

        $content = trim($request->request->get('content', ''));
        $chatMessage->setContent($content);

        $errors = $validator->validate($chatMessage);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', implode(' ', $errorMessages));
        } else {
            $em->flush();
            $this->addFlash('success', 'Message modifie avec succes.');
        }

        return $this->redirectToRoute('guardian_room_detail', ['id' => $chatMessage->getVirtualRoom()->getId()]);
    }

    #[Route('/message/{id<\d+>}/delete', name: 'community_message_delete', methods: ['POST'])]
    public function deleteMessage(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $chatMessage = $em->getRepository(ChatMessage::class)->find($id);
        $user = $this->getUser();

        if (!$chatMessage) {
            $this->addFlash('error', 'Message introuvable.');
            return $this->redirectToRoute('guardian_rooms');
        }

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($chatMessage->getSender() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de supprimer ce message.');
            return $this->redirectToRoute('guardian_room_detail', ['id' => $chatMessage->getVirtualRoom()->getId()]);
        }

        $roomId = $chatMessage->getVirtualRoom()->getId();
        $em->remove($chatMessage);
        $em->flush();

        $this->addFlash('success', 'Message supprime avec succes.');
        return $this->redirectToRoute('guardian_room_detail', ['id' => $roomId]);
    }

    // ============================================================================
    // SHARED TASK ROUTES (Sending Challenges to Friends)
    // ============================================================================

    #[Route('/challenge/send', name: 'community_challenge_send', methods: ['GET', 'POST'])]
    public function sendChallenge(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $sharedWithId = $request->request->get('shared_with_id');

            $sharedWithUser = $em->getRepository(User::class)->find($sharedWithId);

            if (!$sharedWithUser) {
                $this->addFlash('error', 'Utilisateur destinataire introuvable.');
                return $this->redirectToRoute('community_challenge_send');
            }

            // Create SharedTask entity for validation
            $sharedTask = new SharedTask();
            $sharedTask->setTitle($title);
            $sharedTask->setDescription($description);
            $sharedTask->setSharedBy($user);
            $sharedTask->setSharedWith($sharedWithUser);

            // Validate using Symfony Assertions (server-side)
            $errors = $validator->validate($sharedTask);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $this->addFlash('error', implode(' ', $errorMessages));
            } else {
                $em->persist($sharedTask);
                $em->flush();
                $this->addFlash('success', 'Défi envoyé avec succès !');
                return $this->redirectToRoute('community_challenge_inbox');
            }
        }

        // Get all users except current user for sending challenge
        $users = $em->getRepository(User::class)->findAll();
        $users = array_filter($users, fn($u) => $u !== $user);

        return $this->render('community/send_challenge.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/challenge/inbox', name: 'community_challenge_inbox', methods: ['GET'])]
    public function challengeInbox(
        SharedTaskRepository $sharedTaskRepo,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $status = $request->query->get('status');
        $tasks = $sharedTaskRepo->findBySharedWith($user->getId(), $status);

        return $this->render('community/challenge_inbox.html.twig', [
            'tasks' => $tasks,
            'filterStatus' => $status,
        ]);
    }

    #[Route('/challenge/outbox', name: 'community_challenge_outbox', methods: ['GET'])]
    public function challengeOutbox(
        SharedTaskRepository $sharedTaskRepo,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $status = $request->query->get('status');
        $tasks = $sharedTaskRepo->findBySharedBy($user->getId(), $status);

        return $this->render('community/challenge_outbox.html.twig', [
            'tasks' => $tasks,
            'filterStatus' => $status,
        ]);
    }

    #[Route('/challenge/{id<\d+>}/respond', name: 'community_challenge_respond', methods: ['POST'])]
    public function respondToChallenge(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $sharedTask = $em->getRepository(SharedTask::class)->find($id);
        $user = $this->getUser();

        if (!$sharedTask) {
            $this->addFlash('error', 'Défi introuvable.');
            return $this->redirectToRoute('community_index');
        }

        // Only recipient can respond
        if ($sharedTask->getSharedWith() !== $user) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de répondre à ce défi.');
            return $this->redirectToRoute('community_challenge_inbox');
        }

        $response = $request->request->get('response');

        if (!in_array($response, ['accepted', 'rejected'])) {
            $this->addFlash('error', 'Réponse invalide.');
            return $this->redirectToRoute('community_challenge_inbox');
        }

        $sharedTask->setStatus($response);
        $sharedTask->setRespondedAt(new \DateTimeImmutable());
        $em->flush();

        $message = ($response === 'accepted') ? 'Défi accepté !' : 'Défi rejeté.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('community_challenge_inbox');
    }

    // ============================================================================
    // CLAIM ROUTES (Support Ticket System)
    // ============================================================================

    #[Route('/claim/create', name: 'community_claim_create', methods: ['GET', 'POST'])]
    public function createClaim(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $priority = trim($request->request->get('priority', 'medium'));

            // Create Claim entity for validation
            $claim = new Claim();
            $claim->setTitle($title);
            $claim->setDescription($description);
            $claim->setPriority($priority);
            $claim->setCreatedBy($user);

            // Validate using Symfony Assertions (server-side)
            $errors = $validator->validate($claim);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $this->addFlash('error', implode(' ', $errorMessages));
            } else {
                $em->persist($claim);
                $em->flush();
                $this->addFlash('success', 'Ticket créé avec succès. Nous vous répondrons très bientôt.');
                return $this->redirectToRoute('community_claim_list');
            }
        }

        return $this->render('community/create_claim.html.twig');
    }

    #[Route('/claim/{id<\d+>}', name: 'community_claim_view', methods: ['GET'])]
    public function viewClaim(int $id, EntityManagerInterface $em): Response
    {
        $claim = $em->getRepository(Claim::class)->find($id);
        $user = $this->getUser();

        if (!$claim) {
            $this->addFlash('error', 'Ticket introuvable.');
            return $this->redirectToRoute('community_claim_list');
        }

        // Only creator or admin can view
        if ($claim->getCreatedBy() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de voir ce ticket.');
            return $this->redirectToRoute('community_claim_list');
        }

        return $this->render('community/view_claim.html.twig', [
            'claim' => $claim,
        ]);
    }

    #[Route('/claim/list', name: 'community_claim_list', methods: ['GET'])]
    public function listUserClaims(ClaimRepository $claimRepo): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $claims = $claimRepo->findByUser($user->getId());

        return $this->render('community/claims_list.html.twig', [
            'claims' => $claims,
        ]);
    }

    // ============================================================================
    // ADMIN ROUTES (Backoffice for Claims Management)
    // ============================================================================

    #[Route('/admin/claims', name: 'admin_community_claims', methods: ['GET'])]
    public function adminListClaims(ClaimRepository $claimRepo, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        if ($status) {
            $claims = $claimRepo->findByStatusAndPriority($status, $priority);
        } else {
            $claims = $claimRepo->findOpenClaims();
        }

        $openCount = $claimRepo->countOpenClaims();

        return $this->render('admin/community_claims.html.twig', [
            'claims' => $claims,
            'filterStatus' => $status,
            'filterPriority' => $priority,
            'openClaimsCount' => $openCount,
        ]);
    }

    #[Route('/admin/claim/{id<\d+>}/update-status', name: 'admin_community_claim_update_status', methods: ['POST'])]
    public function updateClaimStatus(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $claim = $em->getRepository(Claim::class)->find($id);

        if (!$claim) {
            $this->addFlash('error', 'Ticket introuvable.');
            return $this->redirectToRoute('admin_community_claims');
        }

        $status = $request->request->get('status');
        $priority = $request->request->get('priority');
        $adminNotes = trim($request->request->get('admin_notes', ''));

        // Validate status and priority
        if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'])) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_community_claims');
        }

        if (!in_array($priority, ['low', 'medium', 'high', 'critical'])) {
            $this->addFlash('error', 'Priorité invalide.');
            return $this->redirectToRoute('admin_community_claims');
        }

        $claim->setStatus($status);
        $claim->setPriority($priority);
        $claim->setAdminNotes($adminNotes);

        if ($status === 'resolved') {
            $claim->setResolvedAt(new \DateTimeImmutable());
        }

        // Validate
        $errors = $validator->validate($claim);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', implode(' ', $errorMessages));
        } else {
            $em->flush();
            $this->addFlash('success', 'Ticket mis à jour avec succès.');
        }

        return $this->redirectToRoute('admin_community_claims');
    }

    #[Route('/admin/claim/{id<\d+>}/assign', name: 'admin_community_claim_assign', methods: ['POST'])]
    public function assignClaim(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $claim = $em->getRepository(Claim::class)->find($id);
        $assignedToId = $request->request->get('assigned_to_id');

        if (!$claim) {
            $this->addFlash('error', 'Ticket introuvable.');
            return $this->redirectToRoute('admin_community_claims');
        }

        if ($assignedToId) {
            $assignedTo = $em->getRepository(User::class)->find($assignedToId);

            if (!$assignedTo) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('admin_community_claims');
            }

            $claim->setAssignedTo($assignedTo);
        } else {
            $claim->setAssignedTo(null);
        }

        $em->flush();
        $this->addFlash('success', 'Ticket assigné avec succès.');

        return $this->redirectToRoute('admin_community_claims');
    }
}
