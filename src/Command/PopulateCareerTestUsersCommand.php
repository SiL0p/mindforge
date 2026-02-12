<?php

namespace App\Command;

use App\Entity\Architect\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:populate-career-test-users',
    description: 'Populate the database with 6 test users for Career module (from StaticUserProvider)',
)]
class PopulateCareerTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Populating Career Module Test Users');

        // Password for all test users: password123
        $testUsers = [
            [
                'email' => 'tech.corp@example.com',
                'roles' => ['ROLE_COMPANY', 'ROLE_USER'],
                'name' => 'Tech Corp (Company)'
            ],
            [
                'email' => 'startup.inc@example.com',
                'roles' => ['ROLE_COMPANY', 'ROLE_USER'],
                'name' => 'Startup Inc (Company)'
            ],
            [
                'email' => 'innovate.labs@example.com',
                'roles' => ['ROLE_COMPANY', 'ROLE_USER'],
                'name' => 'Innovate Labs (Company)'
            ],
            [
                'email' => 'student1@example.com',
                'roles' => ['ROLE_USER'],
                'name' => 'Student 1'
            ],
            [
                'email' => 'student2@example.com',
                'roles' => ['ROLE_USER'],
                'name' => 'Student 2'
            ],
            [
                'email' => 'student3@example.com',
                'roles' => ['ROLE_USER'],
                'name' => 'Student 3'
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($testUsers as $userData) {
            // Check if user already exists
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

            if ($existingUser) {
                $io->warning("User already exists: {$userData['email']}");
                $skipped++;
                continue;
            }

            // Create new user
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setRoles($userData['roles']);
            $user->setIsVerified(true); // Auto-verify test users

            $this->em->persist($user);
            $created++;
        }

        $this->em->flush();

        $io->success("✅ Created {$created} test users");
        if ($skipped > 0) {
            $io->note("⏭️  Skipped {$skipped} existing users");
        }

        $io->section('Test Users Created:');
        $io->table(
            ['Email', 'Password', 'Roles'],
            [
                ['tech.corp@example.com', 'password123', 'ROLE_COMPANY, ROLE_USER'],
                ['startup.inc@example.com', 'password123', 'ROLE_COMPANY, ROLE_USER'],
                ['innovate.labs@example.com', 'password123', 'ROLE_COMPANY, ROLE_USER'],
                ['student1@example.com', 'password123', 'ROLE_USER'],
                ['student2@example.com', 'password123', 'ROLE_USER'],
                ['student3@example.com', 'password123', 'ROLE_USER'],
            ]
        );

        return Command::SUCCESS;
    }
}
