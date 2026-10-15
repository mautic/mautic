<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\PageBundle\Entity\Page;

final class Version20210330075323 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName(Page::TABLE_NAME);
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($tableName)->hasColumn('public_preview'),
            sprintf('Column %s already exists in table %s', 'public_preview', $tableName)
        );
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('pages'))->addColumn('public_preview', 'boolean', ['default' => true]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('pages'))->dropColumn('public_preview');
    }
}
