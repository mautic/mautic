<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\SymfonyTextGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Exception\UnexpectedResultTypeException;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\ObjectResult;
use Symfony\AI\Platform\Test\MockPlatformFactory;

final class SymfonyTextGeneratorTest extends TestCase
{
    private const string MODEL = 'test-model';

    public function testReturnsTextUsingTheSelectedModelAndSeparateMessageRoles(): void
    {
        $platform = MockPlatformFactory::createPlatform(static function (Model $model, array|string $payload): string {
            self::assertSame(self::MODEL, $model->getName());
            self::assertSame([
                'messages' => [
                    ['role' => 'system', 'content' => 'Rewrite the subject line.'],
                    ['role' => 'user', 'content' => 'News from the team'],
                ],
                'model' => self::MODEL,
            ], $payload);

            return 'What the team has been working on';
        });

        $generator = new SymfonyTextGenerator($platform);

        $this->assertSame('What the team has been working on', $generator->generate(self::MODEL, 'Rewrite the subject line.', 'News from the team'));
    }

    public function testRejectsANonTextResult(): void
    {
        $platform  = MockPlatformFactory::createPlatform([self::MODEL => new ObjectResult(['answer' => 'Not plain text'])]);
        $generator = new SymfonyTextGenerator($platform);

        $this->expectException(UnexpectedResultTypeException::class);

        $generator->generate(self::MODEL, 'Give a short answer.', 'What is new?');
    }
}
