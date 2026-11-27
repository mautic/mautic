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
     * Skips the migration when the index already exists.
     */
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->getTable($this->getPrefixedTableName())->hasIndex(self::INDEX_NAME),
            'Index '.self::INDEX_NAME.' already exists'
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
     * Adds the index.
     *
     * @param Schema $schema the current database schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' ADD INDEX '.self::INDEX_NAME.' (redirect_id)');
    }
}
