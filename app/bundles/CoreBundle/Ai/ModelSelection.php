<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal
 */
#[Exclude]
final readonly class ModelSelection
{
    public function __construct(
        private ?ConfiguredModel $routine = null,
        private ?ConfiguredModel $complex = null,
    ) {
    }

    /**
     * @param non-empty-list<ModelCapability> $required
     * @param non-negative-int                $contextTokens Total context needed, including room for the response
     */
    public function select(ModelWorkload $workload, array $required, int $contextTokens = 0, bool $allowCloud = false): ?ConfiguredModel
    {
        $model = match ($workload) {
            ModelWorkload::Routine => $this->routine,
            ModelWorkload::Complex => $this->complex,
        };

        if (null === $model || (!$allowCloud && ProviderLocation::Cloud === $model->provider->location())) {
            return null;
        }

        if (($model->contextWindow ?? 0) < $contextTokens || !array_all($required, static fn (ModelCapability $capability): bool => true === $model->supports($capability))) {
            return null;
        }

        return $model;
    }
}
