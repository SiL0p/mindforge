<?php

declare(strict_types=1);

namespace App\Tests\Planner;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlannerModuleRoutesTest extends WebTestCase
{
    public function testPlannerRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/planner', $router->generate('app_planner_entry'));
        self::assertSame('/planner/hub', $router->generate('app_planner_hub'));
        self::assertSame('/planner/tasks', $router->generate('app_planner_tasks'));
        self::assertSame('/planner/exams', $router->generate('app_planner_exams'));
        self::assertSame('/planner/calendar', $router->generate('app_planner_calendar'));
        self::assertSame('/planner/tasks/new', $router->generate('app_planner_task_new'));
    }

    public function testPlannerEntryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/planner');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testPlannerHubRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/planner/hub');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testPlannerTasksRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/planner/tasks');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testPlannerExamsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/planner/exams');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testPlannerCalendarRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/planner/calendar');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
