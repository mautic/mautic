<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * In Mautic 4, we enabled dynamic database detection. Previously, we put a hardcoded
 * db_server_version in your config/local.php, leading to issues when using MariaDB
 * or us bumping the minimum required database version. This config parameter is no
 * longer needed starting with Mautic 4, hence this migration.
 */
final class Version20210502162314 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $confFile = dirname(__DIR__).'/config/local.php';

        if (!file_exists($confFile)) {
            throw new SkipMigration('No config/local.php file found, skipping this migration');
        }

        require $confFile;

        /** @phpstan-ignore-next-line */
        if (isset($parameters) && array_key_exists('db_server_version', $parameters)) {
            unset($parameters['db_server_version']);
        } else {
            throw new SkipMigration('No db_server_version parameter found in config/local.php, skipping this migration');
        }

        // Write updated config to local.php
        $result = file_put_contents($confFile, "<?php\n".'$parameters = '.var_export($parameters, true).';');

        if (false === $result) {
            throw new \Exception('Couldn\'t update configuration file to remove the unsupported db_server_version parameter');
        }
    }
}
