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
    /**
     * @param list<ConfiguredModel> $models In administrator priority order within each location and tier.
     */
    public function __construct(
        private array $models,
    ) {
    }

    /**
     * @param non-empty-list<ModelCapability> $required
     * @param non-negative-int                $contextTokens Total context needed, including room for the response
     */
    public function select(array $required, ModelTier $minimumTier, int $contextTokens = 0, bool $allowCloud = false): ?ConfiguredModel
    {
        $best = null;
        foreach ($this->models as $model) {
            if (!$this->isEligible($model, $required, $minimumTier, $contextTokens, $allowCloud)) {
                continue;
            }

            if (null === $best || $this->hasPriority($model, $best)) {
                $best = $model;
            }
        }

        return $best;
    }

    /**
     * @param list<ModelCapability> $required
     */
    private function isEligible(ConfiguredModel $model, array $required, ModelTier $minimumTier, int $contextTokens, bool $allowCloud): bool
    {
        if (!$allowCloud && ProviderLocation::Cloud === $model->provider->location()) {
            return false;
        }

        if ($model->tier->value < $minimumTier->value || ($model->contextWindow ?? 0) < $contextTokens) {
            return false;
        }

        return array_all($required, static fn (ModelCapability $capability): bool => true === $model->supports($capability));
    }

    private function hasPriority(ConfiguredModel $candidate, ConfiguredModel $best): bool
    {
        if ($candidate->provider->location() !== $best->provider->location()) {
            return ProviderLocation::Local === $candidate->provider->location();
        }

        return $candidate->tier->value < $best->tier->value;
    }
}
