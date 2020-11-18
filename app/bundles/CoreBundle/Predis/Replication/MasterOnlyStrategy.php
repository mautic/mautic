<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Predis\Replication;

use Predis\Replication\ReplicationStrategy;

class MasterOnlyStrategy extends ReplicationStrategy
{
    protected function getReadOnlyOperations(): array
    {
        return [];
    }
}
