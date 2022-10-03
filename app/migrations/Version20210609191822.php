<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
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
