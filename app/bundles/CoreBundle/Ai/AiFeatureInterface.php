<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(name: self::TAG)]
interface AiFeatureInterface
{
    public const string TAG = 'mautic.ai.feature';

    public function id(): string;

    /**
     * @return string|non-empty-list<string>|null A Mautic permission, any one of the listed permissions, or null for administrators only
     */
    public function permission(): string|array|null;

    public function workload(): ModelWorkload;

    public function requiredProviderLocation(): ?ProviderLocation;

    /**
     * @return list<string> Top-level scalar fields the feature may send to the model
     */
    public function allowedContextFields(): array;

    public function instructions(): string;

    /**
     * @return array<string, mixed> JSON Schema for the response object
     */
    public function responseSchema(): array;
}
