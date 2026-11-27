<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;
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
        return $this->generateResult($model, $instructions, $prompt, $options)->text;
    }

    /**
     * @param non-empty-string|Model $model
     * @param array<string, mixed>   $options
     */
    public function generateResult(string|Model $model, #[\SensitiveParameter] string $instructions, #[\SensitiveParameter] string $prompt, array $options = []): AiTextResult
    {
        $messages = new MessageBag(Message::forSystem($instructions), Message::ofUser($prompt));
        $result   = $this->platform->invoke($model, $messages, $options);
        $text     = $result->asText();
        $usage    = $result->getMetadata()->get('token_usage');
        if (!$usage instanceof TokenUsageInterface) {
            return new AiTextResult($text);
        }

        return new AiTextResult($text, $usage->getPromptTokens(), $usage->getCompletionTokens(), $usage->getTotalTokens());
    }
}
