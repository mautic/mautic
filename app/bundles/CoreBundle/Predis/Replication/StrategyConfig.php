<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Predis\Replication;

final class StrategyConfig
{
    /**
     * Use primary Redis server for reads and writes only.
     * The secondary Redis replicas will not be used at all when TRUE.
     */
    public bool $primaryOnly = false;

    /**
     * @param mixed[] $options
     */
    public static function fromArray(array $options): self
    {
        $self              = new StrategyConfig();
        $self->primaryOnly = $options['primaryOnly'] ?? false;

        return $self;
    }
}
