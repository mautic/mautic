<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

/**
 * @internal
 */
interface ModelProviderInterface
{
    public function location(): ProviderLocation;

    /**
     * @param non-empty-string $model
     */
    public function chat(string $model, string $instructions, string $prompt): AiTextResult;

    /**
     * Returns a decoded JSON object. The consumer must validate it against its schema before use.
     *
     * @param non-empty-string     $model
     * @param array<string, mixed> $schema
     */
    public function structured(string $model, string $instructions, string $prompt, array $schema): \stdClass;

    /**
     * @param non-empty-string $model
     *
     * @return list<float>
     */
    public function embed(string $model, string $text): array;
}
