<?php
namespace App\Service;


use App\Entity\Architect\User;
use Symfony\Component\Filesystem\Filesystem;

class FriendService
{
    private string $storageFile;
    private Filesystem $filesystem;

    public function __construct(string $projectDir)
    {
        $this->storageFile = $projectDir . '/var/data/friends.json';
        $this->filesystem = new Filesystem();
        
        // Create directory if it doesn't exist
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir);
        }
        
        // Create file if it doesn't exist
        if (!file_exists($this->storageFile)) {
            $this->filesystem->dumpFile($this->storageFile, json_encode([]));
        }
    }

    private function loadData(): array
    {
        $content = file_get_contents($this->storageFile);
        return json_decode($content, true) ?: [];
    }

    private function saveData(array $data): void
    {
        $this->filesystem->dumpFile($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function sendFriendRequest(User $from, User $to): bool
    {
        if ($from->getId() === $to->getId()) {
            return false; // Can't friend yourself
        }

        $data = $this->loadData();
        $fromId = (string)$from->getId();
        $toId = (string)$to->getId();

        // Initialize user data if not exists
        if (!isset($data[$fromId])) {
            $data[$fromId] = ['friends' => [], 'sent_requests' => [], 'received_requests' => []];
        }
        if (!isset($data[$toId])) {
            $data[$toId] = ['friends' => [], 'sent_requests' => [], 'received_requests' => []];
        }

        // Check if already friends
        if (in_array($toId, $data[$fromId]['friends'])) {
            return false;
        }

        // Check if request already sent
        if (in_array($toId, $data[$fromId]['sent_requests'])) {
            return false;
        }

        // Add request
        $data[$fromId]['sent_requests'][] = $toId;
        $data[$toId]['received_requests'][] = $fromId;

        $this->saveData($data);
        return true;
    }

    public function acceptFriendRequest(User $user, User $requester): bool
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();
        $requesterId = (string)$requester->getId();

        if (!isset($data[$userId]) || !isset($data[$requesterId])) {
            return false;
        }

        // Check if request exists
        if (!in_array($requesterId, $data[$userId]['received_requests'])) {
            return false;
        }

        // Remove from requests
        $data[$userId]['received_requests'] = array_diff($data[$userId]['received_requests'], [$requesterId]);
        $data[$requesterId]['sent_requests'] = array_diff($data[$requesterId]['sent_requests'], [$userId]);

        // Add to friends
        $data[$userId]['friends'][] = $requesterId;
        $data[$requesterId]['friends'][] = $userId;

        // Re-index arrays
        $data[$userId]['received_requests'] = array_values($data[$userId]['received_requests']);
        $data[$requesterId]['sent_requests'] = array_values($data[$requesterId]['sent_requests']);

        $this->saveData($data);
        return true;
    }

    public function rejectFriendRequest(User $user, User $requester): bool
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();
        $requesterId = (string)$requester->getId();

        if (!isset($data[$userId]) || !isset($data[$requesterId])) {
            return false;
        }

        // Remove from requests
        $data[$userId]['received_requests'] = array_values(
            array_diff($data[$userId]['received_requests'], [$requesterId])
        );
        $data[$requesterId]['sent_requests'] = array_values(
            array_diff($data[$requesterId]['sent_requests'], [$userId])
        );

        $this->saveData($data);
        return true;
    }

    public function removeFriend(User $user, User $friend): bool
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();
        $friendId = (string)$friend->getId();

        if (!isset($data[$userId]) || !isset($data[$friendId])) {
            return false;
        }

        // Remove from friends list
        $data[$userId]['friends'] = array_values(
            array_diff($data[$userId]['friends'], [$friendId])
        );
        $data[$friendId]['friends'] = array_values(
            array_diff($data[$friendId]['friends'], [$userId])
        );

        $this->saveData($data);
        return true;
    }

    public function getFriends(User $user): array
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();

        if (!isset($data[$userId])) {
            return [];
        }

        return $data[$userId]['friends'] ?? [];
    }

    public function getSentRequests(User $user): array
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();

        if (!isset($data[$userId])) {
            return [];
        }

        return $data[$userId]['sent_requests'] ?? [];
    }

    public function getReceivedRequests(User $user): array
    {
        $data = $this->loadData();
        $userId = (string)$user->getId();

        if (!isset($data[$userId])) {
            return [];
        }

        return $data[$userId]['received_requests'] ?? [];
    }

    public function areFriends(User $user1, User $user2): bool
    {
        $data = $this->loadData();
        $userId1 = (string)$user1->getId();
        $userId2 = (string)$user2->getId();

        if (!isset($data[$userId1])) {
            return false;
        }

        return in_array($userId2, $data[$userId1]['friends'] ?? []);
    }

    public function hasPendingRequest(User $from, User $to): bool
    {
        $data = $this->loadData();
        $fromId = (string)$from->getId();
        $toId = (string)$to->getId();

        if (!isset($data[$fromId])) {
            return false;
        }

        return in_array($toId, $data[$fromId]['sent_requests'] ?? []);
    }
}