<?php

namespace Mautic\CoreBundle\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as BasePostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\Platforms\PostgreSQLPlatform;

class PostgreSQLMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            public function getDatabasePlatform(): AbstractPlatform
            {
                $platform = parent::getDatabasePlatform();

                // If the detected platform is any version of Postgres, swap to our custom one
                return $platform instanceof BasePostgreSQLPlatform
                    ? new PostgreSQLPlatform()
                    : $platform;
            }
        };
    }
}
