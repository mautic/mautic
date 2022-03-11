<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20180702014364 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable($this->prefix.'lead_fields')->hasColumn('column_is_not_created')) {
            throw new SkipMigration('Schema does not need this migration');
        }

        if ('0' === $schema->getTable($this->prefix.'lead_fields')->getColumn('column_is_not_created')->getDefault()) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_fields MODIFY column_is_not_created TINYINT(1) NOT NULL DEFAULT 0");
    }
}
