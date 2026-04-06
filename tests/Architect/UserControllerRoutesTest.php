<?php

declare(strict_types=1);

namespace App\Tests\Architect;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerRoutesTest extends WebTestCase
{
    public function testPublicUserRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/', $router->generate('app_home'));
        self::assertSame('/user', $router->generate('app_user'));
        self::assertSame('/login', $router->generate('app_login'));
        self::assertSame('/signup', $router->generate('app_signup'));
        self::assertSame('/forgot-password', $router->generate('app_forgot_password'));
        self::assertSame('/workspace', $router->generate('app_workspace'));
    }

    public function testHomePageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testSignupPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/signup');

        self::assertResponseIsSuccessful();
    }

    public function testWorkspaceRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/workspace');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testRequestStatusRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/my-request-status');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
