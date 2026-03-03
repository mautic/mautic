<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201007003589 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->prefix.'lead_fields')->hasColumn('column_is_not_removed')) {
            throw new SkipMigration('Schema does not need this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_fields ADD column_is_not_removed TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_fields DROP column_is_not_removed");
    }
}
