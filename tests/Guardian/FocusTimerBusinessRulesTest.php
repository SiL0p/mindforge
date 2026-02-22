<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use App\Entity\Architect\User;
use App\Entity\Planner\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FocusTimerBusinessRulesTest extends WebTestCase
{
    public function testFocusLogAutoUpdatesTaskProgress(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())
            ->setEmail('focus.rule.'.uniqid('', true).'@example.test')
            ->setPassword('test-password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);

        $task = (new Task())
            ->setTitle('Business rule task '.uniqid('', true))
            ->setOwner($user)
            ->setPriority(Task::PRIORITY_MEDIUM)
            ->setEstimatedMinutes(20)
            ->setStatus(Task::STATUS_TODO);

        try {
            $em->persist($user);
            $em->persist($task);
            $em->flush();

            $client->loginUser($user);

            $client->request('GET', '/guardian/focus-timer?task='.$task->getId());
            self::assertResponseIsSuccessful();

            $content = (string) $client->getResponse()->getContent();
            self::assertMatchesRegularExpression("/_token:\s*'([^']+)'/", $content);
            preg_match("/_token:\s*'([^']+)'/", $content, $matches);
            $csrfToken = (string) ($matches[1] ?? '');
            self::assertNotSame('', $csrfToken);

            $payload = [
                '_token' => $csrfToken,
                'task_id' => $task->getId(),
                'duration' => 35,
                'elapsed_minutes' => 25,
                'client_session_id' => 'business-rule-'.uniqid('', true),
            ];

            $client->request(
                'POST',
                '/guardian/focus-timer/api/log',
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                    'HTTP_ACCEPT' => 'application/json',
                ],
                (string) json_encode($payload, JSON_UNESCAPED_UNICODE)
            );

            self::assertResponseIsSuccessful();

            $json = json_decode((string) $client->getResponse()->getContent(), true);
            self::assertIsArray($json);
            self::assertTrue((bool) ($json['success'] ?? false));
            self::assertSame(Task::STATUS_DONE, $json['task_progress']['status'] ?? null);
            self::assertSame(25, (int) ($json['session']['duration'] ?? 0));

            $em->clear();
            $savedTask = $em->getRepository(Task::class)->find($task->getId());

            self::assertInstanceOf(Task::class, $savedTask);
            self::assertSame(Task::STATUS_DONE, $savedTask->getStatus());
            self::assertSame(25, $savedTask->getActualMinutes());
        } finally {
            $managedTask = $em->getRepository(Task::class)->find($task->getId());
            if ($managedTask instanceof Task) {
                $em->remove($managedTask);
            }

            $managedUser = $em->getRepository(User::class)->find($user->getId());
            if ($managedUser instanceof User) {
                $em->remove($managedUser);
            }

            $em->flush();
        }
    }
}
