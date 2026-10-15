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
        $schema->getTable($this->getPrefixedTableName('lead_fields'))->addColumn('column_is_not_removed', 'boolean', ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('lead_fields'))->dropColumn('column_is_not_removed');
    }
}
