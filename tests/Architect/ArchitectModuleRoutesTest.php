<?php

declare(strict_types=1);

namespace App\Tests\Architect;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArchitectModuleRoutesTest extends WebTestCase
{
    public function testArchitectRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/profile/edit', $router->generate('app_profile_edit'));
        self::assertSame('/profile', $router->generate('app_profile_view'));
        self::assertSame('/profile/generate-avatar', $router->generate('app_generate_avatar'));
        self::assertSame('/profile/avatar-preview', $router->generate('app_avatar_preview'));
        self::assertSame('/profile/avatar-builder', $router->generate('app_avatar_builder'));
        self::assertSame('/friends/', $router->generate('app_friends'));
        self::assertSame('/emotion', $router->generate('app_emotion'));
        self::assertSame('/quotes', $router->generate('app_quotes'));
    }

    public function testProfileEditRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile/edit');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testFriendsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/friends/');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testEmotionPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/emotion');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testEmotionSaveReturnsUnauthorizedWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('POST', '/emotion/save', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'emotion' => 'happy',
            'confidence' => 0.85,
        ]));

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testQuotesPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/quotes');

        self::assertResponseIsSuccessful();
    }
}
