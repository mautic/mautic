<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MigrationCommandSubscriberTest extends MauticMysqlTestCase
{
    private string $tablePrefix;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * Turn off transaction rollback because schema-altering queries cause
     * transactions to be committed automatically.
     */
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tablePrefix     = static::getContainer()->getParameter('mautic.db_table_prefix');
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');
    }

    protected function beforeTearDown(): void
    {
        $this->dropTable('test_first');
        $this->dropTable('test_second');
    }

    public function testGeneratedColumnConfiguration(): void
    {
        // Create test tables
        $this->createTables();

        try {
            // Define test generated columns
            $generatedColumns = [];

            // Add a first column to test correct SQL generation
            $column1                            = new GeneratedColumn('test_first', 'generated_name_one', 'CHAR(2)', 'SUBSTRING(name, 1, 2)');
            $generatedColumns['test_first_one'] = $column1;

            // Add a second column to test stored columns
            $column2 = new GeneratedColumn('test_second', 'generated_date_year', 'YEAR', 'YEAR(date_added)');
            $column2->prependIndexColumn('campaign_id');
            $column2->addIndexColumn('id');
            $column2->setStored(true);
            $generatedColumns['test_second_year'] = $column2;

            // Verify SQL generation
            $columnDefinition = $column1->getColumnDefinition();
            Assert::assertStringContainsString('CHAR(2) AS (SUBSTRING(name, 1, 2))', $columnDefinition);

            $columnDefinition = $column2->getColumnDefinition();
            Assert::assertStringContainsString('YEAR AS (YEAR(date_added)) STORED', $columnDefinition);

            // Test SQL for column 1
            $addColumnSql = $column1->getAddColumnSql();
            Assert::assertStringContainsString('ADD generated_name_one CHAR(2) AS (SUBSTRING(name, 1, 2))', $addColumnSql);

            // Test SQL for column 2
            $addColumnSql = $column2->getAddColumnSql();
            Assert::assertStringContainsString('ADD generated_date_year YEAR AS (YEAR(date_added)) STORED', $addColumnSql);

            $addIndexSql = $column2->getAddIndexSql();
            Assert::assertStringContainsString('ADD INDEX', $addIndexSql);
            Assert::assertStringContainsString('campaign_id', $addIndexSql);
            Assert::assertStringContainsString('generated_date_year', $addIndexSql);
            Assert::assertStringContainsString('id', $addIndexSql);

            // Apply the column to the database for further tests
            $this->connection->executeQuery("ALTER TABLE {$this->tablePrefix}test_first ".$column1->getAddColumnSql());
            $this->connection->executeQuery("ALTER TABLE {$this->tablePrefix}test_first ADD INDEX `{$this->tablePrefix}generated_name_one`(generated_name_one)");

            $this->connection->executeQuery("ALTER TABLE {$this->tablePrefix}test_second ".$column2->getAddColumnSql());
            $this->connection->executeQuery("ALTER TABLE {$this->tablePrefix}test_second ".$column2->getAddIndexSql());

            // Verify the columns were added
            $this->assertTableHasColumnAndIndex('test_first', 'generated_name_one', 'generated_name_one');
            $this->assertTableHasColumnAndIndex('test_second', 'generated_date_year', 'campaign_id_generated_date_year_id');
        } catch (\Exception $e) {
            $this->dropTable('test_first');
            $this->dropTable('test_second');
            throw $e;
        }
    }

    private function assertTableHasColumnAndIndex(string $table, string $column, string $index): void
    {
        $result = $this->connection->fetchAllAssociative("SHOW COLUMNS FROM {$this->tablePrefix}{$table} WHERE Field = '{$column}'");
        Assert::assertNotEmpty($result, sprintf('Table "%s" is expected to have column "%s".', $table, $column));

        $result = $this->connection->fetchAllAssociative("SHOW INDEX FROM {$this->tablePrefix}{$table} WHERE Key_name = '{$this->tablePrefix}{$index}'");
        Assert::assertNotEmpty($result, sprintf('Table "%s" is expected to have index "%s".', $table, $index));
    }

    private function createTables(): void
    {
        $this->dropTable('test_first');
        $this->dropTable('test_second');

        $this->connection->executeQuery("
            CREATE TABLE {$this->tablePrefix}test_first
            (
                id int unsigned not null,
                name varchar(100) NOT NULL,
                primary key (id)
            )
        ");

        $this->connection->executeQuery("
            CREATE TABLE {$this->tablePrefix}test_second
            (
                id int unsigned not null,
                campaign_id int not null,
                date_added datetime NOT NULL,
                primary key (id)
            )
        ");
    }

    private function dropTable(string $table): void
    {
        $this->connection->executeQuery('DROP TABLE IF EXISTS '.$this->tablePrefix.$table);
    }
}
