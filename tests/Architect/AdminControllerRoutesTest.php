<?php

declare(strict_types=1);

namespace App\Tests\Architect;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerRoutesTest extends WebTestCase
{
    public function testAdminRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/admin', $router->generate('admin_dashboard'));
        self::assertSame('/admin/ai/smart-search', $router->generate('admin_ai_smart_search'));
        self::assertSame('/admin/ai/generate-report', $router->generate('admin_ai_report'));
        self::assertSame('/admin/users', $router->generate('admin_users'));
        self::assertSame('/admin/role-requests', $router->generate('admin_role_requests'));
        self::assertSame('/admin/users/view/1', $router->generate('admin_users_view', ['id' => 1]));
    }

    public function testAdminDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminUsersRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminRoleRequestsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/role-requests');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminSmartSearchRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/ai/smart-search?q=user');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminGenerateReportRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/ai/generate-report', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
