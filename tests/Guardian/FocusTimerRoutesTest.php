<?php

namespace App\Tests\Guardian;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FocusTimerRoutesTest extends WebTestCase
{
    public function testFocusRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/guardian/focus-timer', $router->generate('guardian_focus_timer'));
        self::assertSame('/guardian/focus-sessions/log', $router->generate('guardian_focus_session_log'));
    }

    public function testFocusTimerRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/focus-timer');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testFocusSessionLogRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/guardian/focus-sessions/log', [
            'task_id' => 1,
            'duration' => 25,
            'elapsed_minutes' => 1,
            '_token' => 'invalid',
        ]);

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
