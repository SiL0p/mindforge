<?php

namespace App\Service;

use App\Entity\Architect\User;
use Symfony\Component\Filesystem\Filesystem;

class WorkspaceSocialService
{
    private string $storageFile;
    private Filesystem $filesystem;

    public function __construct(string $projectDir)
    {
        $this->storageFile = $projectDir.'/var/data/workspace_social.json';
        $this->filesystem = new Filesystem();

        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir);
        }

        if (!file_exists($this->storageFile)) {
            $this->filesystem->dumpFile($this->storageFile, json_encode([
                'presence' => [],
                'conversations' => [],
            ], JSON_PRETTY_PRINT));
        }
    }

    public function touchPresence(User $user): void
    {
        $data = $this->loadData();
        $userId = (string) $user->getId();

        $data['presence'][$userId] ??= [
            'last_seen_at' => null,
            'focus_mode' => false,
        ];

        $data['presence'][$userId]['last_seen_at'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->saveData($data);
    }

    public function setFocusMode(User $user, bool $focusMode): void
    {
        $data = $this->loadData();
        $userId = (string) $user->getId();

        $data['presence'][$userId] ??= [
            'last_seen_at' => null,
            'focus_mode' => false,
        ];

        $data['presence'][$userId]['last_seen_at'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        $data['presence'][$userId]['focus_mode'] = $focusMode;
        $this->saveData($data);
    }

    public function isFocusMode(User $user): bool
    {
        $data = $this->loadData();
        $userId = (string) $user->getId();

        return (bool) ($data['presence'][$userId]['focus_mode'] ?? false);
    }

    /**
     * @param User[] $friends
     */
    public function getPresenceByUsers(array $friends, int $onlineWindowSeconds = 150): array
    {
        $data = $this->loadData();
        $now = new \DateTimeImmutable();
        $result = [];

        foreach ($friends as $friend) {
            $friendId = (string) $friend->getId();
            $presence = $data['presence'][$friendId] ?? [];
            $lastSeenAtRaw = $presence['last_seen_at'] ?? null;
            $lastSeenAt = $lastSeenAtRaw ? new \DateTimeImmutable($lastSeenAtRaw) : null;

            $isOnline = false;
            if ($lastSeenAt) {
                $isOnline = ($now->getTimestamp() - $lastSeenAt->getTimestamp()) <= $onlineWindowSeconds;
            }

            $result[$friendId] = [
                'online' => $isOnline,
                'focus_mode' => (bool) ($presence['focus_mode'] ?? false),
                'last_seen_at' => $lastSeenAtRaw,
            ];
        }

        return $result;
    }

    public function getConversation(User $a, User $b, int $limit = 40): array
    {
        $data = $this->loadData();
        $key = $this->conversationKey((int) $a->getId(), (int) $b->getId());
        $messages = $data['conversations'][$key] ?? [];

        if ($limit > 0 && count($messages) > $limit) {
            return array_slice($messages, -$limit);
        }

        return $messages;
    }

    public function sendPrivateMessage(User $from, User $to, string $content): array
    {
        $data = $this->loadData();
        $key = $this->conversationKey((int) $from->getId(), (int) $to->getId());

        $data['conversations'][$key] ??= [];

        $message = [
            'id' => bin2hex(random_bytes(8)),
            'from_user_id' => (int) $from->getId(),
            'to_user_id' => (int) $to->getId(),
            'from_label' => $from->getUserIdentifier(),
            'to_label' => $to->getUserIdentifier(),
            'content' => mb_substr(trim($content), 0, 1200),
            'sent_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        $data['conversations'][$key][] = $message;

        if (count($data['conversations'][$key]) > 200) {
            $data['conversations'][$key] = array_slice($data['conversations'][$key], -200);
        }

        $this->saveData($data);

        $recipientPresence = $data['presence'][(string) $to->getId()] ?? [];
        $recipientFocusMode = (bool) ($recipientPresence['focus_mode'] ?? false);

        return [
            'message' => $message,
            'recipient_focus_mode' => $recipientFocusMode,
            'notify_popup' => !$recipientFocusMode,
        ];
    }

    /**
     * @param User[] $friends
     */
    public function getInboxUpdates(User $currentUser, array $friends): array
    {
        $data = $this->loadData();
        $updates = [];
        $currentUserId = (int) $currentUser->getId();

        foreach ($friends as $friend) {
            $friendId = (int) $friend->getId();
            $key = $this->conversationKey($currentUserId, $friendId);
            $messages = $data['conversations'][$key] ?? [];

            if ($messages === []) {
                continue;
            }

            for ($index = count($messages) - 1; $index >= 0; --$index) {
                $message = $messages[$index];
                $toUserId = (int) ($message['to_user_id'] ?? 0);
                if ($toUserId !== $currentUserId) {
                    continue;
                }

                $updates[(string) $friendId] = [
                    'id' => (string) ($message['id'] ?? ''),
                    'content' => (string) ($message['content'] ?? ''),
                    'from_label' => (string) ($message['from_label'] ?? $friend->getUserIdentifier()),
                    'sent_at' => (string) ($message['sent_at'] ?? ''),
                ];

                break;
            }
        }

        return $updates;
    }

    private function conversationKey(int $userIdA, int $userIdB): string
    {
        $ids = [$userIdA, $userIdB];
        sort($ids);

        return $ids[0].'_'.$ids[1];
    }

    private function loadData(): array
    {
        $content = @file_get_contents($this->storageFile);
        $data = is_string($content) ? json_decode($content, true) : null;

        if (!is_array($data)) {
            return [
                'presence' => [],
                'conversations' => [],
            ];
        }

        $data['presence'] ??= [];
        $data['conversations'] ??= [];

        return $data;
    }

    private function saveData(array $data): void
    {
        $this->filesystem->dumpFile($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
