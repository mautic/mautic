<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230131133732 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}lead_lists")->hasColumn('last_built_time')) {
            throw new SkipMigration("The last_built_time column has already been added to the {$this->prefix}lead_lists table.");
        }
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('lead_lists'))->addColumn('last_built_time', 'float', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('lead_lists'))->dropColumn('last_built_time');
    }
}
