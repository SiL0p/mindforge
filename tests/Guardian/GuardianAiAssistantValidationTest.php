<?php

declare(strict_types=1);

namespace App\Tests\Guardian;

use App\Service\Guardian\GuardianAiAssistant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

class GuardianAiAssistantValidationTest extends TestCase
{
    public function testRejectsUnsupportedResourceType(): void
    {
        $assistant = new GuardianAiAssistant(new MockHttpClient());

        $result = $assistant->validateLearningResourceRequest(
            'Mathematics',
            'Linear algebra revision sheet',
            'Need practice exercises for exam prep',
            'docx'
        );

        self::assertFalse($result['valid']);
        self::assertSame('Requested file type is not allowed for AI generation.', $result['message']);
    }

    public function testRejectsFootballRequest(): void
    {
        $assistant = new GuardianAiAssistant(new MockHttpClient());

        $result = $assistant->validateLearningResourceRequest(
            'General',
            'Create a football tactics guide',
            'I want to analyze premier league teams',
            'summary'
        );

        self::assertFalse($result['valid']);
        self::assertSame('Only study-related educational content is allowed. Sports topics are blocked.', $result['message']);
    }

    public function testRejectsRequestWithoutStudySignals(): void
    {
        $assistant = new GuardianAiAssistant(new MockHttpClient());

        $result = $assistant->validateLearningResourceRequest(
            'General',
            'Write a lifestyle productivity blog post',
            'I want a cool inspirational text',
            'summary'
        );

        self::assertFalse($result['valid']);
        self::assertSame('Only study-related educational content is allowed. Please provide an academic learning request.', $result['message']);
    }

    public function testAcceptsStudyRequestWithAllowedType(): void
    {
        $assistant = new GuardianAiAssistant(new MockHttpClient());

        $result = $assistant->validateLearningResourceRequest(
            'Mathematics',
            'Vector spaces and basis revision',
            'Need concise notes and exercises for final exam',
            'pdf'
        );

        self::assertTrue($result['valid']);
        self::assertNull($result['message']);
    }
}
