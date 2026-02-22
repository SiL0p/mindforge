<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LibraryExternalApiRoutesTest extends WebTestCase
{
    public function testExternalSuggestionsRouteIsRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/guardian/library/api/external-suggestions', $router->generate('guardian_library_api_external_suggestions'));
    }

    public function testExternalSuggestionsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/guardian/library/api/external-suggestions?q=math');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
}
