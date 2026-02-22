<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use App\Entity\Architect\User;
use App\Entity\Guardian\AiInsight;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAiInsightPageTest extends WebTestCase
{
    public function testAiInsightsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/guardian/ai-insights');

        self::assertTrue($client->getResponse()->isRedirect());
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminCanSeeAiInsightsPageWithPersistedInsight(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        try {
            $em->getConnection()->executeQuery('SELECT 1');
        } catch (\Throwable $exception) {
            self::markTestSkipped('Test database is not available: '.$exception->getMessage());
        }

        $token = 'guardian-ai-test-'.uniqid('', true);

        $admin = (new User())
            ->setEmail('admin.ai.'.uniqid('', true).'@example.test')
            ->setPassword('test-password')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true);

        $insight = (new AiInsight())
            ->setUser($admin)
            ->setType(AiInsight::TYPE_DAILY_PLAN)
            ->setSource('rule')
            ->setPayload((string) json_encode([
                'response' => [
                    'plan' => ['Block 1', 'Block 2', $token],
                ],
            ], JSON_UNESCAPED_UNICODE));

        try {
            $em->persist($admin);
            $em->persist($insight);
            $em->flush();

            $client->loginUser($admin);
            $client->request('GET', '/admin/guardian/ai-insights');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('h1', 'Guardian AI Insights');
            self::assertStringContainsString($token, (string) $client->getResponse()->getContent());
        } finally {
            if ($em->contains($insight)) {
                $em->remove($insight);
            }

            if ($em->contains($admin)) {
                $em->remove($admin);
            }

            $em->flush();
        }
    }
}
