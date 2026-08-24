<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;

final class CheckDatabaseDriverAndVersion extends AbstractPreUpdateCheck
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function runCheck(): PreUpdateCheckResult
    {
        $metadata        = $this->getUpdateCandidateMetadata();
        $connection      = $this->em->getConnection();
        $versionProvider = new VersionProvider($connection);

        // Version strings are in the format:
        // 10.3.30-MariaDB-1:10.3.30+maria~focal-log
        // PostgreSQL 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1) on x86_64-pc-linux-gnu, compiled by gcc (Ubuntu 13.3.0-6ubuntu2~24.04) 13.3.0, 64-bit
        $version = $versionProvider->getVersion();

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
        } elseif (str_contains($platform, 'postgresql')) {
            $minSupported = $metadata->getMinSupportedPostgreSqlVersion();
        } else {
            $supportedDrivers = implode(', ', DoctrineStep::getDriverKeys());

            return new PreUpdateCheckResult(false, $this, [new PreUpdateCheckError('mautic.core.update.check.database_driver',
                [
                    '%currentdriver%'    => $platform,
                    '%supporteddrviers%' => $supportedDrivers,
                ]
            )]);
        }

        if (version_compare($versionProvider::getNumericVersion($version), $minSupported, '<')) {
            return new PreUpdateCheckResult(false, $this, [new PreUpdateCheckError('mautic.core.update.check.database_version',
                [
                    '%currentversion%'          => $version,
                    '%mysqlminversion%'         => $metadata->getMinSupportedMySqlVersion(),
                    '%mariadbminversion%'       => $metadata->getMinSupportedMariaDbVersion(),
                    '%postgresqlminversion%'    => $metadata->getMinSupportedPostgreSqlVersion(),
                ]),
            ]);
        }

        return new PreUpdateCheckResult(true, $this);
    }
}
