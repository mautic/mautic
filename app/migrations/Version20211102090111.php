<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211102090111 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $confFile = dirname(__DIR__).'/config/local.php';

        if (false === $this->doMigration($confFile)) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $confFile = dirname(__DIR__).'/config/local.php';

        if (false === $this->doMigration($confFile)) {
            return;
        }

        require $confFile;

        $parameters['show_contact_channels'] = true;

        // Write updated config to local.php
        $result = file_put_contents($confFile, "<?php\n".'$parameters = '.var_export($parameters, true).';');

        if (false === $result) {
            throw new \Exception('Couldn\'t update configuration file with new db_server_version value (5.7).');
        }
    }

    private function doMigration($confFile)
    {
        if (!file_exists($confFile)) {
            return false;
        }

        require $confFile;

        /** @phpstan-ignore-next-line */
        if (isset($parameters) && !array_key_exists('show_contact_channels', $parameters)) {
            if ($parameters['show_contact_frequency'] ?? false || $parameters['show_contact_pause_dates'] ?? false) {
                return true;
            }
        }

        return false;
    }
}
