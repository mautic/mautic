<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200507122854 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $fieldsTable = $schema->getTable($this->prefix.'form_fields');

        if ($fieldsTable->hasColumn('parent_id')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE {$this->prefix}form_fields ADD parent_id VARCHAR(255) DEFAULT NULL;"
        );
    }
}
