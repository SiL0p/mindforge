<?php

namespace App\Service;

use App\Entity\Architect\Profile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarService
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    public function upload(UploadedFile $file, Profile $profile): string
    {
        $filename = uniqid('avatar_') . '.' . $file->guessExtension();

        $file->move($this->uploadDir, $filename);

        // Delete old avatar if exists
        if ($profile->getAvatar()) {
            $oldFile = $this->uploadDir . '/' . $profile->getAvatar();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        return $filename;
    }

    public function delete(Profile $profile): void
    {
        if ($profile->getAvatar()) {
            $oldFile = $this->uploadDir . '/' . $profile->getAvatar();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    }
}