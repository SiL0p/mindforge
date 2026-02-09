<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class StaticUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $email,
        private string $password, // hashed password
        private array $roles,
        private ?string $companyName = null // for company users
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        // Ensure ROLE_USER is always included
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase for static users
    }
}

class StaticUserProvider implements UserProviderInterface
{
    private array $users;

    public function __construct()
    {
        // All users have password 'password123'
        $hashedPassword = '$2y$10$yr3yRmgpy/5QXH9XP4zf9u5eFpnBw8i9opSc92Bu9lWbKv2qE4FmK';

        // Hardcoded users
        $this->users = [
            // Companies
            'tech.corp@example.com' => new StaticUser(
                email: 'tech.corp@example.com',
                password: $hashedPassword,
                roles: ['ROLE_COMPANY'],
                companyName: 'Tech Corporation'
            ),
            'startup.inc@example.com' => new StaticUser(
                email: 'startup.inc@example.com',
                password: $hashedPassword,
                roles: ['ROLE_COMPANY'],
                companyName: 'Startup Inc'
            ),
            'innovate.labs@example.com' => new StaticUser(
                email: 'innovate.labs@example.com',
                password: $hashedPassword,
                roles: ['ROLE_COMPANY'],
                companyName: 'Innovate Labs'
            ),

            // Students
            'student1@example.com' => new StaticUser(
                email: 'student1@example.com',
                password: $hashedPassword,
                roles: []
            ),
            'student2@example.com' => new StaticUser(
                email: 'student2@example.com',
                password: $hashedPassword,
                roles: []
            ),
            'student3@example.com' => new StaticUser(
                email: 'student3@example.com',
                password: $hashedPassword,
                roles: []
            ),
        ];
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!isset($this->users[$identifier])) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $this->users[$identifier];
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof StaticUser) {
            throw new \InvalidArgumentException('Invalid user class');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return StaticUser::class === $class || is_subclass_of($class, StaticUser::class);
    }

    public function getUserByEmail(string $email): ?StaticUser
    {
        return $this->users[$email] ?? null;
    }

    public function getAllUsers(): array
    {
        return $this->users;
    }
}
