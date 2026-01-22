<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MigrationCommandSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;
    private string $tablePrefix;
    private EventDispatcherInterface $eventDispatcher;

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

    public function testMigrationsAreExecuted(): void
    {
        $this->createTables();

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event) {
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_one', 'CHAR(2)', 'SUBSTRING(name, 1, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_two', 'CHAR(2)', 'SUBSTRING(name, 3, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_three', 'CHAR(2)', 'SUBSTRING(name, 5, 2)'));
        });

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event) {
            $generatedColumn = new GeneratedColumn('test_second', 'generated_date_year', 'YEAR', 'YEAR(date_added)');
            $generatedColumn->prependIndexColumn('campaign_id');
            $generatedColumn->addIndexColumn('id');
            $generatedColumn->setStored(true);
            $event->addGeneratedColumn($generatedColumn);
        });

        $output = $this->executeMigrationCommand();

        // Relaxed, platform-agnostic checks – we only verify that the expected steps were executed
        Assert::assertStringContainsString("adding generated columns for table {$this->tablePrefix}test_first", $output);
        Assert::assertStringContainsString("adding indices for table {$this->tablePrefix}test_first", $output);
        Assert::assertStringContainsString("adding generated columns for table {$this->tablePrefix}test_second", $output);
        Assert::assertStringContainsString("adding indices for table {$this->tablePrefix}test_second", $output);

        // Platform-agnostic verification of columns and indexes via schema introspection
        $this->assertGeneratedColumnsAndIndexesExist();
    }

    private function assertGeneratedColumnsAndIndexesExist(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // test_first
        $tableFirst = $schemaManager->introspectTable($this->tablePrefix.'test_first');

        Assert::assertTrue($tableFirst->hasColumn('generated_name_one'), 'generated_name_one column missing');
        Assert::assertTrue($tableFirst->hasColumn('generated_name_three'), 'generated_name_three column missing');

        $hasIndexOne   = $this->hasSingleColumnIndex($tableFirst, 'generated_name_one');
        $hasIndexThree = $this->hasSingleColumnIndex($tableFirst, 'generated_name_three');

        Assert::assertTrue($hasIndexOne, 'Index on generated_name_one missing');
        Assert::assertTrue($hasIndexThree, 'Index on generated_name_three missing');

        // test_second
        $tableSecond = $schemaManager->introspectTable($this->tablePrefix.'test_second');

        Assert::assertTrue($tableSecond->hasColumn('generated_date_year'), 'generated_date_year column missing');

        $hasCompositeIndex = $this->hasCompositeIndex($tableSecond, ['campaign_id', 'generated_date_year', 'id']);

        Assert::assertTrue($hasCompositeIndex, 'Composite index on (campaign_id, generated_date_year, id) missing');
    }

    private function hasSingleColumnIndex(\Doctrine\DBAL\Schema\Table $table, string $column): bool
    {
        foreach ($table->getIndexes() as $index) {
            $columns = $index->getColumns();
            if (1 === count($columns) && $columns[0] === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $expectedColumns
     */
    private function hasCompositeIndex(\Doctrine\DBAL\Schema\Table $table, array $expectedColumns): bool
    {
        foreach ($table->getIndexes() as $index) {
            if ($index->getColumns() === $expectedColumns) {
                return true;
            }
        }

        return false;
    }

    private function createTables(): void
    {
        $isPostgreSQL = $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;

        $idType   = $isPostgreSQL ? 'integer' : 'int unsigned';
        $dateType = $isPostgreSQL ? 'timestamp' : 'datetime';

        // Generated column syntax differs significantly between MySQL and PostgreSQL
        $generatedColumnSql = $isPostgreSQL
            ? 'generated_name_two CHAR(2) GENERATED ALWAYS AS (substring(name from 3 for 2)) STORED,'
            : 'generated_name_two CHAR(2) AS (SUBSTRING(name, 3, 2)),';

        // test_first (pre-creates one generated column to test skipping duplicates)
        $sqlFirst = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_first (
    id $idType NOT NULL,
    name varchar(100) NOT NULL,
    $generatedColumnSql
    PRIMARY KEY (id)
)
SQL;

        $this->connection->executeStatement($sqlFirst);

        // test_second (no pre-existing generated column)
        $sqlSecond = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_second (
    id $idType NOT NULL,
    campaign_id integer NOT NULL,
    date_added $dateType NOT NULL,
    PRIMARY KEY (id)
)
SQL;

        $this->connection->executeStatement($sqlSecond);
    }

    private function dropTable(string $table): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.$this->tablePrefix.$table);
    }

    private function executeMigrationCommand(): string
    {
        // intentionally not using AbstractMauticTestCase::testSymfonyCommand() as it does not dispatch 'console.terminate' event
        $params      = ['command' => 'doctrine:migration:migrate', '--allow-no-migration' => true, '--no-interaction' => true];
        $application = new Application(static::getContainer()->get('kernel'));
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $output     = new BufferedOutput();
        $statusCode = $application->run(new ArrayInput($params), $output);
        $message    = $output->fetch();

        Assert::assertSame(ExitCode::SUCCESS, $statusCode, $message);

        return $message;
    }
}
