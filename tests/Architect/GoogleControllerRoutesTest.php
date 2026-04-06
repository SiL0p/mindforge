<?php

declare(strict_types=1);

namespace App\Tests\Architect;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GoogleControllerRoutesTest extends WebTestCase
{
    public function testGoogleControllerRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/connect/google', $router->generate('connect_google_start'));
        self::assertSame('/connect/google/check', $router->generate('connect_google_check'));
        self::assertSame('/connect/google', $router->generate('app_google_connect'));
        self::assertSame('/connect/google/callback', $router->generate('app_google_callback'));
    }

    public function testConnectGoogleStartPath(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');
        self::assertSame('/connect/google', $router->generate('connect_google_start'));
    }

    public function testConnectGoogleCheckPath(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');
        self::assertSame('/connect/google/check', $router->generate('connect_google_check'));
    }

    public function testUserControllerGoogleConnectPath(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');
        self::assertSame('/connect/google', $router->generate('app_google_connect'));
    }

    public function testUserControllerGoogleCallbackPath(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');
        self::assertSame('/connect/google/callback', $router->generate('app_google_callback'));
    }

    public function testGoogleRoutesCanBeGeneratedMultipleTimes(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertNotSame('', $router->generate('connect_google_start'));
        self::assertNotSame('', $router->generate('connect_google_check'));
        self::assertNotSame('', $router->generate('app_google_connect'));
        self::assertNotSame('', $router->generate('app_google_callback'));
    }
}
