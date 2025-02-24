<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Predis\Replication;

use Predis\Replication\ReplicationStrategy;

class MasterOnlyStrategy extends ReplicationStrategy
{
    public function __construct(
        private StrategyConfig $config
    ) {
        parent::__construct();
    }

    /**
     * @return mixed[]
     */
    protected function getReadOnlyOperations(): array
    {
        if ($this->config->usePrimaryOnly()) {
            return [];
        }

        return parent::getReadOnlyOperations();
    }

    /**
     * @return mixed[]
     */
    protected function getDisallowedOperations(): array
    {
        $operations = parent::getDisallowedOperations();
        unset($operations['INFO']); // removed to avoid "The command 'INFO' is not allowed in replication mode." error when executing bin/console cache:clear. INFO is safe if you only have one master that handles all operations.

        return $operations;
    }
}
