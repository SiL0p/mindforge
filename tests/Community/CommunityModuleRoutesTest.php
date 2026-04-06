<?php

declare(strict_types=1);

namespace App\Tests\Community;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommunityModuleRoutesTest extends WebTestCase
{
    public function testCommunityRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/community', $router->generate('community_index'));
        self::assertSame('/community/challenge/inbox', $router->generate('community_challenge_inbox'));
        self::assertSame('/community/challenge/outbox', $router->generate('community_challenge_outbox'));
        self::assertSame('/community/tasks', $router->generate('community_tasks'));
        self::assertSame('/community/claim/list', $router->generate('community_claim_list'));
        self::assertSame('/community/admin/claims', $router->generate('admin_community_claims'));
    }

    public function testCommunityIndexLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/community');

        self::assertResponseIsSuccessful();
    }

    public function testCommunityMessageSendRouteGeneration(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/community/room/1/message/send', $router->generate('community_message_send', ['id' => 1]));
    }

    public function testCommunityClaimViewRouteGeneration(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/community/claim/1', $router->generate('community_claim_view', ['id' => 1]));
    }

    public function testCommunityChallengeRespondRouteGeneration(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/community/challenge/1/respond', $router->generate('community_challenge_respond', ['id' => 1]));
    }

    public function testCommunityChallengeCategoryRouteGeneration(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get('router');

        self::assertSame('/community/challenges/category/math', $router->generate('community_challenges_by_category', ['category' => 'math']));
    }
}
