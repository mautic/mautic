<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\Migrations\Version20261117155532;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PageRedirectsRedirectIdIndexMigrationTest extends TestCase
{
    /**
     * Checks that the index is added without locking the table when it is missing.
     */
    public function testAddsIndexWithoutLockingTheTable(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema();

        $migration->preUp($schema);
        $migration->up($schema);

        $this->assertSame(
            'ALTER TABLE page_redirects ADD INDEX redirect_id_search (redirect_id), ALGORITHM=INPLACE, LOCK=NONE',
            $migration->getSql()[0]->getStatement()
        );
    }

    /**
     * Checks that the table prefix is applied to the table and to the index.
     */
    public function testUsesTheTablePrefix(): void
    {
        $migration = $this->createMigration('mautic_');
        $schema    = $this->createSchema('mautic_');

        $migration->preUp($schema);
        $migration->up($schema);

        $this->assertSame(
            'ALTER TABLE mautic_page_redirects ADD INDEX mautic_redirect_id_search (redirect_id), ALGORITHM=INPLACE, LOCK=NONE',
            $migration->getSql()[0]->getStatement()
        );
    }

    /**
     * Checks that the migration is skipped when the index already exists.
     */
    public function testSkipsWhenTheIndexAlreadyExists(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema('', ['redirect_id'], 'redirect_id_search');

        $this->expectException(SkipMigration::class);

        $migration->preUp($schema);
    }

    /**
     * Checks that the migration is skipped when another index already starts with redirect_id.
     */
    public function testSkipsWhenAnotherIndexStartsWithRedirectId(): void
    {
        $migration = $this->createMigration();
        $schema    = $this->createSchema('', ['redirect_id', 'hits'], 'custom_redirect_lookup');

        $this->expectException(SkipMigration::class);

        $migration->preUp($schema);
    }

    /**
     * Builds a page_redirects table with an optional extra index.
     *
     * @param string   $prefix       the table prefix
     * @param string[] $indexColumns the columns of the extra index, none when empty
     * @param string   $indexName    the name of the extra index
     *
     * @return Schema the schema holding the table
     */
    private function createSchema(string $prefix = '', array $indexColumns = [], string $indexName = ''): Schema
    {
        $schema = new Schema();
        $table  = $schema->createTable($prefix.'page_redirects');
        $table->addColumn('id', 'bigint');
        $table->addColumn('redirect_id', 'string', ['length' => 25]);
        $table->addColumn('hits', 'integer');
        $table->setPrimaryKey(['id']);

        if ($indexColumns) {
            $table->addIndex($indexColumns, $prefix.$indexName);
        }

        return $schema;
    }

    /**
     * Creates the migration with a stubbed connection.
     *
     * @param string $prefix the table prefix
     *
     * @return Version20261117155532 the migration under test
     */
    private function createMigration(string $prefix = ''): Version20261117155532
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());

        $migration = new Version20261117155532($connection, new NullLogger());
        $migration->setPrefix($prefix);

        return $migration;
    }
}
