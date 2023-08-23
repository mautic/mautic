<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Predis\Replication;

final class StrategyConfig
{
    private bool $primaryOnly = false;

    public function __construct(bool $primaryOnly)
    {
        $this->primaryOnly = $primaryOnly;
    }

    /**
     * @param mixed[] $options
     */
    public static function fromArray(array $options): self
    {
        return new StrategyConfig($options['primaryOnly'] ?? false);
    }

    /**
     * Use primary Redis server for reads and writes only.
     * The secondary Redis replicas will not be used at all when TRUE.
     */
    public function usePrimaryOnly(): bool
    {
        return $this->primaryOnly;
    }
}
