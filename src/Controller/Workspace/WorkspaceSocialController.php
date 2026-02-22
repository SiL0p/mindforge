<?php

namespace App\Controller\Workspace;

use App\Entity\Architect\User;
use App\Repository\Analyst\GamificationStatsRepository;
use App\Service\FriendService;
use App\Service\WorkspaceSocialService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/workspace/network')]
class WorkspaceSocialController extends AbstractController
{
    #[Route('/private-chat/inbox-updates', name: 'app_workspace_network_private_chat_inbox_updates', methods: ['GET'])]
    public function inboxUpdates(
        FriendService $friendService,
        EntityManagerInterface $em,
        WorkspaceSocialService $socialService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $socialService->touchPresence($user);

        $friendIds = $friendService->getFriends($user);
        $friends = [];
        foreach ($friendIds as $friendId) {
            $friend = $em->getRepository(User::class)->find((int) $friendId);
            if ($friend) {
                $friends[] = $friend;
            }
        }

        return $this->json([
            'success' => true,
            'updates' => $socialService->getInboxUpdates($user, $friends),
            'user_focus_mode' => $socialService->isFocusMode($user),
        ]);
    }

    #[Route('', name: 'app_workspace_network_hub', methods: ['GET'])]
    public function hub(
        FriendService $friendService,
        EntityManagerInterface $em,
        GamificationStatsRepository $statsRepository,
        WorkspaceSocialService $socialService
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $socialService->touchPresence($user);

        $friendIds = $friendService->getFriends($user);
        $friends = [];
        foreach ($friendIds as $friendId) {
            $friend = $em->getRepository(User::class)->find((int) $friendId);
            if ($friend) {
                $friends[] = $friend;
            }
        }

        $statsByUser = $statsRepository->findByUsersIndexed($friends);
        $presenceByUser = $socialService->getPresenceByUsers($friends);

        $friendCards = [];
        foreach ($friends as $friend) {
            $friendId = (int) $friend->getId();
            $stats = $statsByUser[$friendId] ?? null;
            $presence = $presenceByUser[(string) $friendId] ?? ['online' => false, 'focus_mode' => false];

            $friendCards[] = [
                'id' => $friendId,
                'label' => $friend->getUserIdentifier(),
                'level' => $stats?->getCurrentLevel() ?? 1,
                'xp' => $stats?->getTotalXp() ?? 0,
                'leaderboard_position' => $statsRepository->findLeaderboardPosition($friend),
                'online' => (bool) ($presence['online'] ?? false),
                'focus_mode' => (bool) ($presence['focus_mode'] ?? false),
            ];
        }

        return $this->render('workspace/network_hub.html.twig', [
            'friendCards' => $friendCards,
        ]);
    }

    #[Route('/status/ping', name: 'app_workspace_network_status_ping', methods: ['POST'])]
    public function pingStatus(WorkspaceSocialService $socialService): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $socialService->touchPresence($user);

        return $this->json(['success' => true]);
    }

    #[Route('/status/focus', name: 'app_workspace_network_status_focus', methods: ['POST'])]
    public function setFocusMode(Request $request, WorkspaceSocialService $socialService): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $focusMode = (bool) ($payload['focus_mode'] ?? false);

        $socialService->setFocusMode($user, $focusMode);

        return $this->json([
            'success' => true,
            'focus_mode' => $focusMode,
        ]);
    }

    #[Route('/friends/{id}/profile', name: 'app_workspace_network_friend_profile', methods: ['GET'])]
    public function friendProfile(
        User $friend,
        FriendService $friendService,
        GamificationStatsRepository $statsRepository,
        WorkspaceSocialService $socialService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if (!$friendService->areFriends($user, $friend)) {
            return $this->json(['success' => false, 'message' => 'Friend access required.'], 403);
        }

        $presence = $socialService->getPresenceByUsers([$friend]);
        $friendStats = $statsRepository->findOneByUser($friend);

        return $this->json([
            'success' => true,
            'friend' => [
                'id' => $friend->getId(),
                'label' => $friend->getUserIdentifier(),
                'online' => (bool) ($presence[(string) $friend->getId()]['online'] ?? false),
                'focus_mode' => (bool) ($presence[(string) $friend->getId()]['focus_mode'] ?? false),
                'level' => $friendStats?->getCurrentLevel() ?? 1,
                'xp' => $friendStats?->getTotalXp() ?? 0,
                'leaderboard_position' => $statsRepository->findLeaderboardPosition($friend),
            ],
        ]);
    }

    #[Route('/private-chat/{id}', name: 'app_workspace_network_private_chat_messages', methods: ['GET'])]
    public function privateChatMessages(
        User $friend,
        FriendService $friendService,
        WorkspaceSocialService $socialService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if (!$friendService->areFriends($user, $friend)) {
            return $this->json(['success' => false, 'message' => 'Friend access required.'], 403);
        }

        $socialService->touchPresence($user);

        return $this->json([
            'success' => true,
            'messages' => $socialService->getConversation($user, $friend),
            'friend_id' => $friend->getId(),
            'friend_label' => $friend->getUserIdentifier(),
        ]);
    }

    #[Route('/private-chat/{id}/send', name: 'app_workspace_network_private_chat_send', methods: ['POST'])]
    public function sendPrivateChatMessage(
        User $friend,
        Request $request,
        FriendService $friendService,
        WorkspaceSocialService $socialService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if (!$friendService->areFriends($user, $friend)) {
            return $this->json(['success' => false, 'message' => 'Friend access required.'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $content = trim((string) ($payload['content'] ?? ''));

        if ($content === '') {
            return $this->json(['success' => false, 'message' => 'Message cannot be empty.'], 422);
        }

        $socialService->touchPresence($user);
        $sendResult = $socialService->sendPrivateMessage($user, $friend, $content);

        return $this->json([
            'success' => true,
            'message' => $sendResult['message'],
            'recipient_focus_mode' => (bool) $sendResult['recipient_focus_mode'],
            'notify_popup' => (bool) $sendResult['notify_popup'],
            'silent_delivery' => (bool) $sendResult['recipient_focus_mode'],
        ]);
    }
}
