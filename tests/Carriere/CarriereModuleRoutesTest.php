<?php

declare(strict_types=1);

namespace App\Tests\Carriere;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CarriereModuleRoutesTest extends WebTestCase
{
    public function testCarriereRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/carriere/opportunite', $router->generate('app_carriere_opportunite_index'));
        self::assertSame('/carriere/entreprise', $router->generate('app_carriere_entreprise_index'));
        self::assertSame('/carriere/quiz/1', $router->generate('app_carriere_quiz_show', ['id' => 1]));
        self::assertSame('/carriere/demande/my-demandes', $router->generate('app_carriere_demande_my_demandes'));
        self::assertSame('/carriere/demande/apply/1', $router->generate('app_carriere_demande_apply', ['id' => 1]));
        self::assertSame('/carriere/opportunite/1', $router->generate('app_carriere_opportunite_show', ['id' => 1]));
    }

    public function testCarriereOpportunityIndexLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carriere/opportunite');

        self::assertResponseIsSuccessful();
    }

    public function testCarriereCompanyIndexLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carriere/entreprise');

        self::assertResponseIsSuccessful();
    }

    public function testMyOpportunitesRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carriere/opportunite/my-opportunites');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testApplyRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carriere/demande/apply/1');

        $statusCode = $client->getResponse()->getStatusCode();

        if ($client->getResponse()->isRedirect()) {
            self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
            return;
        }

        self::assertSame(404, $statusCode);
    }

    public function testCompanyDemandesRouteIsRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/carriere/demande/company/1', $router->generate('app_carriere_demande_company_demandes', ['companyId' => 1]));
    }
}
