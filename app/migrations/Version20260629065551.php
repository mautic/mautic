<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

final class Version20260629065551 extends PreUpAssertionMigration
{
    protected const TABLE_NAME  = 'leads';
    protected const INDEX_NAME  = 'last_active';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $table = $schema->getTable($this->getPrefixedTableName());
            $indexHelper    = $this->container->get(IndexHelper::class);

            return count($table->getIndexes()) >= $indexHelper->getMaxCount() || $table->hasIndex($this->getPrefixedIndexName());
        }, sprintf('Index %s cannot be created because the %s table has hit the index limit or the index already exists', $this->getPrefixedIndexName(), $this->getPrefixedTableName()));
    }

    public function up(Schema $schema): void
    {
        $this->createIndex(
            $this->getPrefixedTableName(),
            $this->getPrefixedIndexName(),
            ['last_active']
        );
    }
}
