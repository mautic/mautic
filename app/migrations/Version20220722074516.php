<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

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
        $platform  = $this->connection->getDatabasePlatform();

        $this->addSql("ALTER TABLE {$this->getTableName()} ADD deduplicate VARCHAR(32) DEFAULT NULL");
        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $platform,
                $this->getTableName(),
                'deduplicate_date_added',
                ['deduplicate', 'date_added']
            )
        );
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();

        $this->addSql(
            DatabasePlatform::getDropIndexSql(
                $platform,
                $this->getTableName(),
                'deduplicate_date_added',
                false,
                true
            )
        );
        $this->addSql("ALTER TABLE {$this->getTableName()} DROP deduplicate");
    }

    private function getTableName(): string
    {
        return $this->prefix.'notifications';
    }
}
