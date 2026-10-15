<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240416112112 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}campaigns")->hasColumn('version');
        }, sprintf('Column %s already exists', 'version'));
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('campaigns'))->addColumn('version', 'integer', ['unsigned' => true, 'default' => 1]);
    }
}
