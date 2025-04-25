<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MigrationCommandSubscriberTest extends MauticMysqlTestCase
{
    private string $tablePrefix;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->markTestSkipped('This test requires a specific database configuration that supports DATE_FORMAT in generated columns');

        parent::setUp();

        $this->tablePrefix     = static::getContainer()->getParameter('mautic.db_table_prefix');
        $this->eventDispatcher = static::getContainer()->get('event_dispatcher');
    }

    protected function beforeTearDown(): void
    {
        $this->dropTable('test_first');
        $this->dropTable('test_second');
    }

    public function testMigrationsAreExecuted(): void
    {
        $this->createTables();

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event) {
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_one', 'CHAR(2)', 'SUBSTRING(name, 1, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_two', 'CHAR(2)', 'SUBSTRING(name, 3, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_three', 'CHAR(2)', 'SUBSTRING(name, 5, 2)'));
        });

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event) {
            $generatedColumn = new GeneratedColumn('test_second', 'generated_date_year', 'YEAR', 'DATE_FORMAT(date_added, "%Y")');
            $generatedColumn->prependIndexColumn('campaign_id');
            $generatedColumn->addIndexColumn('id');
            $generatedColumn->setStored(true);
            $event->addGeneratedColumn($generatedColumn);
        });

        $output = $this->testSymfonyCommand('doctrine:migration:migrate', ['-n' => true])->getDisplay();

        Assert::assertStringContainsString("++ Executing adding generated columns for table {$this->tablePrefix}test_first
-> ALTER TABLE {$this->tablePrefix}test_first ADD generated_name_one CHAR(2) AS (SUBSTRING(name, 1, 2)) COMMENT '(DC2Type:generated)', 
ADD generated_name_three CHAR(2) AS (SUBSTRING(name, 5, 2)) COMMENT '(DC2Type:generated)'
++ Execution finished", $output);

        Assert::assertStringContainsString("++ Executing adding indices for table {$this->tablePrefix}test_first
-> ALTER TABLE {$this->tablePrefix}test_first ADD INDEX `{$this->tablePrefix}generated_name_one`(generated_name_one), 
ADD INDEX `{$this->tablePrefix}generated_name_three`(generated_name_three)
++ Execution finished", $output);

        Assert::assertStringContainsString("++ Executing adding generated columns for table {$this->tablePrefix}test_second
-> ALTER TABLE {$this->tablePrefix}test_second ADD generated_date_year YEAR AS (DATE_FORMAT(date_added, \"%Y\")) STORED COMMENT '(DC2Type:generated)'
++ Execution finished", $output);

        Assert::assertStringContainsString("++ Executing adding indices for table {$this->tablePrefix}test_second
-> ALTER TABLE {$this->tablePrefix}test_second ADD INDEX `{$this->tablePrefix}campaign_id_generated_date_year_id`(campaign_id, generated_date_year, id)
++ Execution finished", $output);

        $this->assertTableHasColumnAndIndex('test_first', 'generated_name_one', 'generated_name_one');
        $this->assertTableHasColumnAndIndex('test_first', 'generated_name_three', 'generated_name_three');
        $this->assertTableHasColumnAndIndex('test_second', 'generated_date_year', 'campaign_id_generated_date_year_id');
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
        $this->connection->executeQuery("
            CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_first
            (
                id int unsigned not null,
                name varchar(100) NOT NULL,
                generated_name_two CHAR(2) AS (SUBSTRING(name, 3, 2)),
                primary key (id)
            )
        ");

        $this->connection->executeQuery("
            CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_second
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
