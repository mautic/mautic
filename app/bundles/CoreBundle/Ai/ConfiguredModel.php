<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal
 */
#[Exclude]
final readonly class ConfiguredModel
{
    /**
     * @param non-empty-string                     $name
     * @param array<value-of<ModelCapability>, bool> $capabilities
     * @param positive-int|null                    $contextWindow
     */
    public function __construct(
        public ModelProviderInterface $provider,
        public string $name,
        private array $capabilities,
        public ?int $contextWindow = null,
        public ?int $providerId = null,
    ) {
    }

    public function supports(ModelCapability $capability): ?bool
    {
        return $this->capabilities[$capability->value] ?? null;
    }
}
