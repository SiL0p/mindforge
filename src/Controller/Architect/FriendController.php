<?php // src/Controller/Architect/FriendController.php
namespace App\Controller\Architect;

use App\Entity\Architect\User;
use App\Service\FriendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/friends')]
class FriendController extends AbstractController
{
    #[Route('/', name: 'app_friends')]
    public function index(
        FriendService $friendService,
        EntityManagerInterface $em
    ): Response {
        // Get the current user
        $user = $this->getUser();
        
        // Check if user is logged in
        if (!$user) {
            $this->addFlash('warning', 'Please login to view your friends.');
            return $this->redirectToRoute('app_login'); // Make sure this route exists
        }

        // Get friend IDs
        $friendIds = $friendService->getFriends($user);
        $sentRequestIds = $friendService->getSentRequests($user);
        $receivedRequestIds = $friendService->getReceivedRequests($user);

        // Convert IDs to User objects
        $friends = [];
        foreach ($friendIds as $friendId) {
            $friend = $em->getRepository(User::class)->find($friendId);
            if ($friend) {
                $friends[] = $friend;
            }
        }

        $sentRequests = [];
        foreach ($sentRequestIds as $requestId) {
            $requestUser = $em->getRepository(User::class)->find($requestId);
            if ($requestUser) {
                $sentRequests[] = $requestUser;
            }
        }

        $receivedRequests = [];
        foreach ($receivedRequestIds as $requestId) {
            $requestUser = $em->getRepository(User::class)->find($requestId);
            if ($requestUser) {
                $receivedRequests[] = $requestUser;
            }
        }

        // Get all users for "Find Friends" section
        $allUsers = $em->getRepository(User::class)->findAll();
        $suggestedUsers = array_filter($allUsers, function($u) use ($user, $friendIds, $sentRequestIds, $receivedRequestIds) {
            return $u->getId() !== $user->getId() 
                && !in_array((string)$u->getId(), $friendIds)
                && !in_array((string)$u->getId(), $sentRequestIds)
                && !in_array((string)$u->getId(), $receivedRequestIds);
        });

        return $this->render('user/friends.html.twig', [
            'friends' => $friends,
            'sent_requests' => $sentRequests,
            'received_requests' => $receivedRequests,
            'suggested_users' => $suggestedUsers,
        ]);
    }

    #[Route('/send-request/{id}', name: 'app_friend_send_request')]
    public function sendRequest(
        User $toUser,
        FriendService $friendService
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($friendService->sendFriendRequest($user, $toUser)) {
            $this->addFlash('success', "Friend request sent to {$toUser->getEmail()}!");
        } else {
            $this->addFlash('error', 'Unable to send friend request.');
        }

        return $this->redirectToRoute('app_friends');
    }

    #[Route('/accept-request/{id}', name: 'app_friend_accept')]
    public function acceptRequest(
        User $requester,
        FriendService $friendService
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($friendService->acceptFriendRequest($user, $requester)) {
            $this->addFlash('success', "You are now friends with {$requester->getEmail()}!");
        } else {
            $this->addFlash('error', 'Unable to accept friend request.');
        }

        return $this->redirectToRoute('app_friends');
    }

    #[Route('/reject-request/{id}', name: 'app_friend_reject')]
    public function rejectRequest(
        User $requester,
        FriendService $friendService
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($friendService->rejectFriendRequest($user, $requester)) {
            $this->addFlash('info', 'Friend request rejected.');
        } else {
            $this->addFlash('error', 'Unable to reject friend request.');
        }

        return $this->redirectToRoute('app_friends');
    }

    #[Route('/remove/{id}', name: 'app_friend_remove')]
    public function removeFriend(
        User $friend,
        FriendService $friendService
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($friendService->removeFriend($user, $friend)) {
            $this->addFlash('info', "Removed {$friend->getEmail()} from your friends.");
        } else {
            $this->addFlash('error', 'Unable to remove friend.');
        }

        return $this->redirectToRoute('app_friends');
    }
}