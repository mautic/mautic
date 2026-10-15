<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Connection;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;

final class PrimaryReadReplicaConnectionWrapper extends PrimaryReadReplicaConnection
{
    use ConnectionInitializerTrait;
}
