<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20180702014365 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable($this->prefix.'lead_fields')->hasColumn('original_is_published_value')) {
            throw new SkipMigration('Schema does not need this migration');
        }

        if ('0' === $schema->getTable($this->prefix.'lead_fields')->getColumn('original_is_published_value')->getDefault()) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_fields MODIFY original_is_published_value TINYINT(1) NOT NULL DEFAULT 0");
    }
}
