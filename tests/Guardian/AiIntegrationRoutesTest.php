<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AiIntegrationRoutesTest extends WebTestCase
{
    public function testAiRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/guardian/focus-timer/api/recommended-duration/1', $router->generate('guardian_focus_timer_api_recommended_duration', ['taskId' => 1]));
        self::assertSame('/guardian/focus-timer/api/tips/1', $router->generate('guardian_focus_timer_api_tips', ['taskId' => 1]));
        self::assertSame('/guardian/focus-timer/api/daily-plan', $router->generate('guardian_focus_timer_api_daily_plan'));
        self::assertSame('/guardian/focus-timer/api/weekly-review', $router->generate('guardian_focus_timer_api_weekly_review'));
    }

    public function testAiRoutesRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/guardian/focus-timer/api/recommended-duration/1');
        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/guardian/focus-timer/api/tips/1');
        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/guardian/focus-timer/api/daily-plan');
        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));

        $client->request('GET', '/guardian/focus-timer/api/weekly-review');
        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
