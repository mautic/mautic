<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Connection;

use Doctrine\DBAL\Connection;

class ConnectionWrapper extends Connection
{
    use ConnectionInitializerTrait;
}
