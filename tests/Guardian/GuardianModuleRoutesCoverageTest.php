<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuardianModuleRoutesCoverageTest extends WebTestCase
{
    public function testGuardianRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/guardian', $router->generate('guardian_hub'));
        self::assertSame('/guardian/library', $router->generate('guardian_library'));
        self::assertSame('/guardian/rooms', $router->generate('guardian_rooms'));
        self::assertSame('/guardian/focus-timer', $router->generate('guardian_focus_timer'));
        self::assertSame('/guardian/stats', $router->generate('guardian_stats'));
        self::assertSame('/guardian/focus-timer/api/overview', $router->generate('guardian_focus_timer_api_overview'));
    }

    public function testGuardianHubRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGuardianLibraryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/library');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGuardianRoomsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/rooms');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGuardianFocusTimerRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/focus-timer');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGuardianStatsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/stats');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
