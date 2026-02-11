<?php

namespace App\Command;

use App\Entity\Architect\User;
use App\Entity\Community\ChatMessage;
use App\Entity\Community\Claim;
use App\Entity\Community\SharedTask;
use App\Entity\Guardian\VirtualRoom;
use App\Entity\Planner\Subject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generate test data for Community Module testing',
)]
class GenerateTestDataCommand extends Command
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

        try {
            // 1. Create Test Users
            $io->section('Creating test users...');
            
            $user1 = new User();
            $user1->setEmail('student1@mindforge.local');
            $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
            $user1->setRoles(['ROLE_USER']);
            $this->em->persist($user1);

            $user2 = new User();
            $user2->setEmail('student2@mindforge.local');
            $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));
            $user2->setRoles(['ROLE_USER']);
            $this->em->persist($user2);

            $user3 = new User();
            $user3->setEmail('admin@mindforge.local');
            $user3->setPassword($this->passwordHasher->hashPassword($user3, 'password123'));
            $user3->setRoles(['ROLE_ADMIN']);
            $this->em->persist($user3);

            $this->em->flush();
            $io->success("✓ Created 3 test users");
            $io->text("  - student1@mindforge.local (ID: {$user1->getId()})");
            $io->text("  - student2@mindforge.local (ID: {$user2->getId()})");
            $io->text("  - admin@mindforge.local (ID: {$user3->getId()})");

            // 2. Create Test Subject
            $io->section('Creating test subject...');
            
            $subject = new Subject();
            $subject->setName('Mathematics');
            $this->em->persist($subject);
            $this->em->flush();
            $io->success("✓ Created test subject: Mathematics (ID: {$subject->getId()})");

            // 3. Create Test Virtual Rooms
            $io->section('Creating test virtual rooms...');
            
            $room1 = new VirtualRoom();
            $room1->setName('Mathematics Discussion');
            $room1->setDescription('Room for discussing mathematical concepts');
            $room1->setCreator($user1);
            $room1->setSubject($subject);
            $room1->addParticipant($user1);
            $room1->addParticipant($user2);
            $this->em->persist($room1);

            $room2 = new VirtualRoom();
            $room2->setName('Physics Study Group');
            $room2->setDescription('Collaborative physics problem solving');
            $room2->setCreator($user2);
            $room2->setSubject($subject);
            $room2->addParticipant($user1);
            $room2->addParticipant($user2);
            $this->em->persist($room2);

            $this->em->flush();
            $io->success("✓ Created 2 test virtual rooms");
            $io->text("  - Mathematics Discussion (ID: {$room1->getId()})");
            $io->text("  - Physics Study Group (ID: {$room2->getId()})");

            // 4. Create Test Chat Messages
            $io->section('Creating test chat messages...');
            
            $message1 = new ChatMessage();
            $message1->setContent('Hello everyone! Let\'s discuss calculus today.');
            $message1->setSender($user1);
            $message1->setVirtualRoom($room1);
            $this->em->persist($message1);

            $message2 = new ChatMessage();
            $message2->setContent('Great idea! I need help with derivatives.');
            $message2->setSender($user2);
            $message2->setVirtualRoom($room1);
            $this->em->persist($message2);

            $this->em->flush();
            $io->success("✓ Created 2 test chat messages");
            $io->text("  - Message 1 (ID: {$message1->getId()})");
            $io->text("  - Message 2 (ID: {$message2->getId()})");

            // 4. Create Test Shared Tasks (Challenges)
            $io->section('Creating test shared tasks (challenges)...');
            
            $challenge1 = new SharedTask();
            $challenge1->setTitle('Solve 10 Algebra Problems');
            $challenge1->setDescription('Complete the algebra problem set from Chapter 5. Due by Friday.');
            $challenge1->setSharedBy($user1);
            $challenge1->setSharedWith($user2);
            $this->em->persist($challenge1);

            $challenge2 = new SharedTask();
            $challenge2->setTitle('Physics Assignment');
            $challenge2->setDescription('Complete the physics lab report on motion and forces.');
            $challenge2->setSharedBy($user2);
            $challenge2->setSharedWith($user1);
            $challenge2->setStatus('accepted');
            $this->em->persist($challenge2);

            $this->em->flush();
            $io->success("✓ Created 2 test shared tasks");
            $io->text("  - Challenge 1 (ID: {$challenge1->getId()})");
            $io->text("  - Challenge 2 (ID: {$challenge2->getId()})");

            // 5. Create Test Claims (Support Tickets)
            $io->section('Creating test support tickets...');
            
            $ticket1 = new Claim();
            $ticket1->setTitle('Cannot access course materials');
            $ticket1->setDescription('I\'m unable to access the course materials for Advanced Mathematics. Every time I try to download a file, I get an error.');
            $ticket1->setPriority('high');
            $ticket1->setStatus('open');
            $ticket1->setCreatedBy($user1);
            $this->em->persist($ticket1);

            $ticket2 = new Claim();
            $ticket2->setTitle('Grade dispute');
            $ticket2->setDescription('I believe my last exam was graded incorrectly. I answered question 5 correctly but received no points.');
            $ticket2->setPriority('medium');
            $ticket2->setStatus('in_progress');
            $ticket2->setCreatedBy($user2);
            $ticket2->setAssignedTo($user3);
            $this->em->persist($ticket2);

            $ticket3 = new Claim();
            $ticket3->setTitle('Technical issue with video streaming');
            $ticket3->setDescription('The live class videos are buffering constantly. Other websites work fine, so it\'s not my connection.');
            $ticket3->setPriority('medium');
            $ticket3->setStatus('open');
            $ticket3->setCreatedBy($user1);
            $this->em->persist($ticket3);

            $this->em->flush();
            $io->success("✓ Created 3 test support tickets");
            $io->text("  - Ticket 1 (ID: {$ticket1->getId()})");
            $io->text("  - Ticket 2 (ID: {$ticket2->getId()})");
            $io->text("  - Ticket 3 (ID: {$ticket3->getId()})");

            // Display comprehensive testing guide
            $io->section('TEST DATA GENERATED - READY FOR TESTING');
            
            $io->writeln("\n<fg=green>LOGIN CREDENTIALS:</>");
            $io->writeln("  Student: student1@mindforge.local (password: password123)");
            $io->writeln("  Student: student2@mindforge.local (password: password123)");
            $io->writeln("  Admin:   admin@mindforge.local (password: password123)");

            $io->writeln("\n<fg=yellow>TESTING PATHS (use IDs from above):</>");
            $io->writeln("\n<fg=cyan>CHAT ROUTES:</>");
            $io->writeln("  GET  http://127.0.0.1:8001/community/room/{$room1->getId()}/chat");
            $io->writeln("  POST http://127.0.0.1:8001/community/room/{$room1->getId()}/message/send [content=message]");
            $io->writeln("  POST http://127.0.0.1:8001/community/message/{$message1->getId()}/edit [content=edited]");
            $io->writeln("  POST http://127.0.0.1:8001/community/message/{$message1->getId()}/delete");

            $io->writeln("\n<fg=cyan>CHALLENGE ROUTES:</>");
            $io->writeln("  GET  http://127.0.0.1:8001/community/challenge/send");
            $io->writeln("  POST http://127.0.0.1:8001/community/challenge/send [title=...&description=...&shared_with_id={$user2->getId()}]");
            $io->writeln("  GET  http://127.0.0.1:8001/community/challenge/inbox");
            $io->writeln("  GET  http://127.0.0.1:8001/community/challenge/outbox");
            $io->writeln("  POST http://127.0.0.1:8001/community/challenge/{$challenge1->getId()}/respond [response=accepted]");

            $io->writeln("\n<fg=cyan>CLAIM ROUTES:</>");
            $io->writeln("  GET  http://127.0.0.1:8001/community/claim/create");
            $io->writeln("  POST http://127.0.0.1:8001/community/claim/create [title=...&description=...&priority=high]");
            $io->writeln("  GET  http://127.0.0.1:8001/community/claim/list");
            $io->writeln("  GET  http://127.0.0.1:8001/community/claim/{$ticket1->getId()}");

            $io->writeln("\n<fg=cyan>ADMIN ROUTES:</>");
            $io->writeln("  GET  http://127.0.0.1:8001/community/admin/claims");
            $io->writeln("  POST http://127.0.0.1:8001/community/admin/claim/{$ticket2->getId()}/update-status [status=resolved&priority=high&admin_notes=Fixed]");
            $io->writeln("  POST http://127.0.0.1:8001/community/admin/claim/{$ticket1->getId()}/assign [assigned_to_id={$user3->getId()}]");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
