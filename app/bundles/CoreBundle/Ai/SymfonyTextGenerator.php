<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal
 */
#[Exclude]
final readonly class SymfonyTextGenerator
{
    public function __construct(
        private PlatformInterface $platform,
    ) {
    }

    /**
     * @param non-empty-string $model
     */
    public function generate(string $model, string $instructions, string $prompt): string
    {
        $messages = new MessageBag(Message::forSystem($instructions), Message::ofUser($prompt));

        return $this->platform->invoke($model, $messages)->asText();
    }
}
