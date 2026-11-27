<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;
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
     * @param non-empty-string|Model $model
     * @param array<string, mixed>   $options
     */
    public function generate(string|Model $model, #[\SensitiveParameter] string $instructions, #[\SensitiveParameter] string $prompt, array $options = []): string
    {
        $messages = new MessageBag(Message::forSystem($instructions), Message::ofUser($prompt));

        return $this->platform->invoke($model, $messages, $options)->asText();
    }
}
