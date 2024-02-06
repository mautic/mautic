<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220722074516 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->getTableName())->hasColumn('deduplicate')) {
            throw new SkipMigration("The deduplicate column has already been added to the {$this->getTableName()} table.");
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getTableName()} ADD deduplicate VARCHAR(32) DEFAULT NULL");
        $this->addSql("CREATE INDEX deduplicate_date_added ON {$this->getTableName()} (deduplicate, date_added)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX deduplicate_date_added ON {$this->getTableName()}");
        $this->addSql("ALTER TABLE {$this->getTableName()} DROP deduplicate");
    }

    private function getTableName(): string
    {
        return $this->prefix.'notifications';
    }
}
