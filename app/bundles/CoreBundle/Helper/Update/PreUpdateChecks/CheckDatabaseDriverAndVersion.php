<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

use Doctrine\ORM\EntityManager;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;

class CheckDatabaseDriverAndVersion extends AbstractPreUpdateCheck
{
    public function __construct(
        private EntityManager $em,
    ) {
    }

    public function runCheck(): PreUpdateCheckResult
    {
        $metadata   = $this->getUpdateCandidateMetadata();
        $connection = $this->em->getConnection();

        // Version strings are in the format 10.3.30-MariaDB-1:10.3.30+maria~focal-log
        $version  = $connection->executeQuery('SELECT VERSION()')->fetchOne();

        // Platform class names are in the format Doctrine\DBAL\Platforms\MariaDb1027Platform
        $platform = strtolower($connection->getDatabasePlatform()::class);

        /**
         * The second case is for MariaDB < 10.2, where Doctrine reports it as MySQLPlatform. Here we can use a little
         * help from the version string, which contains "MariaDB" in that case: 10.1.48-MariaDB-1~bionic.
         */
        if (str_contains($platform, 'mariadb') || str_contains(strtolower($version), 'mariadb')) {
            $minSupported = $metadata->getMinSupportedMariaDbVersion();
        } elseif (str_contains($platform, 'mysql')) {
            $minSupported = $metadata->getMinSupportedMySqlVersion();
        } else {
            $supportedDrivers = implode(', ', DoctrineStep::getDriverKeys());

            return new PreUpdateCheckResult(false, $this, [new PreUpdateCheckError('mautic.core.update.check.database_driver',
                [
                    '%currentdriver%'    => $platform,
                    '%supporteddrviers%' => $supportedDrivers,
                ]
            )]);
        }

        if (true !== version_compare($version, $minSupported, 'gt')) {
            return new PreUpdateCheckResult(false, $this, [new PreUpdateCheckError('mautic.core.update.check.database_version',
                [
                    '%currentversion%'    => $version,
                    '%mysqlminversion%'   => $metadata->getMinSupportedMySqlVersion(),
                    '%mariadbminversion%' => $metadata->getMinSupportedMariaDbVersion(),
                ]),
            ]);
        }

        return new PreUpdateCheckResult(true, $this);
    }
}
