<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PointBundle\Entity\LeadPointLog;

class Version20190319002039 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.LeadPointLog::TABLE_NAME);

        if ($table->hasColumn('internal_id')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->prefix.LeadPointLog::TABLE_NAME;
        $this->addSql("ALTER TABLE {$tableName} ADD internal_id BIGINT UNSIGNED DEFAULT NULL");
        $this->addSql("CREATE INDEX {$this->prefix}internal_id ON {$tableName} (internal_id)");
    }
}
