<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20261117155532 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'page_redirects';
    private const INDEX_NAME = 'redirect_id_search';

    /**
     * Skips the migration when an index that starts with redirect_id already exists.
     */
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => $this->hasRedirectIdIndex($schema),
            'An index on '.self::TABLE_NAME.'.redirect_id already exists'
        );
    }

    /**
     * Describes the migration.
     *
     * @return string the description shown in the migration list
     */
    public function getDescription(): string
    {
        return 'Index page_redirects.redirect_id so a tracked link click does not scan the table';
    }

    /**
     * Adds the index without locking the table.
     *
     * @param Schema $schema the current database schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getPrefixedTableName()} ADD INDEX {$this->getIndexName()} (redirect_id), ALGORITHM=INPLACE, LOCK=NONE");
    }

    /**
     * Tells whether the table has an index whose first column is redirect_id.
     *
     * @param Schema $schema the current database schema
     *
     * @return bool true when such an index exists
     */
    private function hasRedirectIdIndex(Schema $schema): bool
    {
        foreach ($schema->getTable($this->getPrefixedTableName())->getIndexes() as $index) {
            if ('redirect_id' === ($index->getColumns()[0] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the index name with the table prefix.
     *
     * @return string the prefixed index name
     */
    private function getIndexName(): string
    {
        return $this->prefix.self::INDEX_NAME;
    }
}
