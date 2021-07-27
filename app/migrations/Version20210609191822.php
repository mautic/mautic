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

final class Version20210609191822 extends AbstractMauticMigration
{
    private $tables = [
        'oauth1_access_tokens',
        'oauth1_consumers',
        'oauth1_nonces',
        'oauth1_request_tokens',
    ];

    /**
     * Mautic 4 removed OAuth1 support, so we drop those tables if they exist.
     */
    public function up(Schema $schema): void
    {
        foreach ($this->tables as $table) {
            if ($schema->hasTable($this->prefix.$table)) {
                $schema->dropTable($this->prefix.$table);
            }
        }
    }
}
