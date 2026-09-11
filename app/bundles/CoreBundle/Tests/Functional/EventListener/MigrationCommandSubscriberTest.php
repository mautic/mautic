<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class MigrationCommandSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $tablePrefix;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tablePrefix     = self::getContainer()->getParameter('mautic.db_table_prefix');
        $this->eventDispatcher = self::getContainer()->get(EventDispatcherInterface::class);
    }

    protected function beforeTearDown(): void
    {
        $this->dropTable('test_first');
        $this->dropTable('test_second');
    }

    public function testMigrationsAreExecuted(): void
    {
        $this->createTables();

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event): void {
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_one', 'CHAR(2)', 'SUBSTRING(name, 1, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_two', 'CHAR(2)', 'SUBSTRING(name, 3, 2)'));
            $event->addGeneratedColumn(new GeneratedColumn('test_first', 'generated_name_three', 'CHAR(2)', 'SUBSTRING(name, 5, 2)'));
        });

        $this->eventDispatcher->addListener(CoreEvents::ON_GENERATED_COLUMNS_BUILD, function (GeneratedColumnsEvent $event): void {
            $generatedColumn = new GeneratedColumn('test_second', 'generated_date_year', 'YEAR', 'YEAR(date_added)');
            $generatedColumn->prependIndexColumn('campaign_id');
            $generatedColumn->addIndexColumn('id');
            $generatedColumn->setStored(true);
            $event->addGeneratedColumn($generatedColumn);
        });

        $output = $this->executeMigrationCommand();

        if (!$this->isPostgresqlPlatform()) {
            // Relaxed, platform-agnostic checks – we only verify that the expected steps were executed
            $this->assertStringContainsString("adding generated columns for table {$this->tablePrefix}test_first", $output);
            $this->assertStringContainsString("adding indices for table {$this->tablePrefix}test_first", $output);
            $this->assertStringContainsString("adding generated columns for table {$this->tablePrefix}test_second", $output);
            $this->assertStringContainsString("adding indices for table {$this->tablePrefix}test_second", $output);

            // Platform-agnostic verification of columns and indexes via schema introspection
            $this->assertGeneratedColumnsAndIndexesExist();
        } else {
            // We skip generated column test as they are not immutable (so cant be created)
            $this->markTestSkipped('PostgreSQL platform don`t support generated columns');
        }
    }

    private function assertGeneratedColumnsAndIndexesExist(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // test_first
        $schemaManager->introspectTable($this->tablePrefix.'test_first');

        $this->assertTableHasColumnAndIndex('test_first', 'generated_name_one', 'generated_name_one');
        $this->assertTableHasColumnAndIndex('test_first', 'generated_name_three', 'generated_name_three');
        $this->assertTableHasColumnAndIndex('test_second', 'generated_date_year', 'campaign_id_generated_date_year_id');
    }

    private function assertTableHasColumnAndIndex(string $table, string $column, string $index): void
    {
        $result = $this->connection->fetchAssociative("SHOW COLUMNS FROM {$this->tablePrefix}{$table} WHERE Field = '{$column}'");
        $this->assertNotEmpty($result, sprintf('Table "%s" is expected to have column "%s".', $table, $column));

        $result = $this->connection->fetchAssociative("SHOW INDEX FROM {$this->tablePrefix}{$table} WHERE Key_name = '{$this->tablePrefix}{$index}'");
        $this->assertNotEmpty($result, sprintf('Table "%s" is expected to have index "%s".', $table, $index));
    }

    private function createTables(): void
    {
        $platform     = $this->connection->getDatabasePlatform();
        $isPostgreSQL = DatabasePlatform::isPostgreSQL($platform);

        // Generated column syntax differs significantly between MySQL and PostgreSQL
        $generatedColumnSql = DatabasePlatform::getGeneratedColumnDefinition(
            $platform,
            'generated_name_two CHAR(2)',
            $isPostgreSQL ? 'substring(name from 3 for 2)' : 'SUBSTRING(name, 3, 2)'
        );

        $idType   = $isPostgreSQL ? 'integer' : 'int unsigned';
        $dateType = $isPostgreSQL ? 'timestamp' : 'datetime';

        // test_first (pre-creates one generated column to test skipping duplicates)
        $this->connection->executeStatement("
            CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_first (
                id $idType NOT NULL,
                name varchar(100) NOT NULL,
                $generatedColumnSql,
                PRIMARY KEY (id)
            )
        ");

        $this->connection->executeStatement("
            CREATE TABLE IF NOT EXISTS {$this->tablePrefix}test_second
            (
                id $idType NOT NULL,
                campaign_id integer NOT NULL,
                date_added $dateType NOT NULL,
                PRIMARY KEY (id)
            )
        ");
    }

    private function dropTable(string $table): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.$this->tablePrefix.$table);
    }

    private function executeMigrationCommand(): string
    {
        if ($this->isPostgresqlPlatform()) {
            // Doctrine Migrations' TableMetadataStorage::ensureInitialized() is not idempotent on PostgreSQL
            // — it always tries to add the PK via alterTable(), without first verifying if the constraint is already present.
            // This is a long-standing limitation in Doctrine Migrations (especially versions 2.x/3.x),
            // and it's well-known when using PostgreSQL (MySQL forgives duplicate attempts).
            $this->connection->executeStatement('DROP TABLE IF EXISTS migrations CASCADE');
        }
        // intentionally not using AbstractMauticTestCase::testSymfonyCommand() as it does not dispatch 'console.terminate' event
        $params      = ['command' => 'doctrine:migration:migrate', '--allow-no-migration' => true, '--no-interaction' => true];
        $application = new Application(self::getContainer()->get(KernelInterface::class));
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $output     = new BufferedOutput();
        $statusCode = $application->run(new ArrayInput($params), $output);
        $message    = $output->fetch();

        $this->assertSame(ExitCode::SUCCESS, $statusCode, $message);

        return $message;
    }
}
