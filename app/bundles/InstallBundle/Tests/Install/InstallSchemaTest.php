<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Mautic\LeadBundle\Field\Helper\IndexHelper;
use Mautic\Migrations;
use Mautic\Migrations\Version20260604000000;
use Mautic\Migrations\Version20260616075543;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @template T of AbstractPlatform
 */
final class InstallSchemaTest extends TestCase
{
    private Connection $connection;

    /**
     * @var array<string, mixed>
     */
    private array $dbParams;

    private string $indexTableName;

    /**
     * @var AbstractSchemaManager<T>
     */
    private AbstractSchemaManager $schemaManager;

    protected function setUp(): void
    {
        parent::setUp();
        EnvLoader::load();
        if (!Type::hasType(GeneratedType::GENERATED)) {
            Type::addType(GeneratedType::GENERATED, GeneratedType::class);
        }

        $driver = $_ENV['DB_DRIVER'] ?? 'pdo_mysql';

        $this->dbParams = [
            'driver'        => $driver,
            'host'          => $_ENV['DB_HOST'],
            'port'          => $_ENV['DB_PORT'],
            'dbname'        => $_ENV['DB_NAME'], // Doctrine needs 'dbname', not 'name'
            'user'          => $_ENV['DB_USER'],
            'password'      => $_ENV['DB_PASSWD'],
            'charset'       => 'pdo_pgsql' == $driver ? 'UTF8' : 'utf8mb4',
            'table_prefix'  => MAUTIC_TABLE_PREFIX,
            'backup_prefix' => 'bak_',
        ];

        $this->connection = DriverManager::getConnection($this->dbParams);

        $this->indexTableName = 'table_with_index';

        $t = new Table($this->indexTableName);
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->addColumn('a_column', 'text');
        $t->setPrimaryKey(['id']);

        // Create an index that has options, e.g. length of the index
        $indexOptions = [
            'lengths' => [
                0 => 128,
            ],
        ];
        $t->addIndex(['a_column'], 'index_with_options', [], $indexOptions);
        $this->schemaManager = $this->connection->createSchemaManager();
        $this->schemaManager->createTable($t);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->schemaManager->tablesExist([$this->indexTableName])) {
            $this->schemaManager->dropTable($this->indexTableName);
        }
        if ($this->schemaManager->tablesExist([$this->dbParams['backup_prefix'].$this->indexTableName])) {
            $this->schemaManager->dropTable($this->dbParams['backup_prefix'].$this->indexTableName);
        }
        if (DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            foreach ([$this->indexTableName, 'bak_'.$this->indexTableName] as $table) {
                $this->connection->executeStatement('DROP SEQUENCE IF EXISTS '.$table.'_id_seq');
            }
        }
        $this->connection->close();
    }

    public function testBackupIndexesWithConfigOptions(): void
    {
        $this->executeSchemaOperation('backupExistingSchema');
        $this->assertTrue($this->schemaManager->tablesExist(['bak_'.$this->indexTableName]));
    }

    #[DataProvider('sequenceOwnership')]
    public function testPostgreSqlBackupPreservesSequencesAcrossReinstall(bool $owned): void
    {
        if (!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $this->markTestSkipped('PostgreSQL sequence ownership and index names.');
        }

        if (!$owned) {
            $this->connection->executeStatement('ALTER SEQUENCE '.$this->indexTableName.'_id_seq OWNED BY NONE');
        }

        $this->connection->insert($this->indexTableName, ['a_column' => 'original']);
        $table = $this->schemaManager->introspectTable($this->indexTableName);

        $this->executeSchemaOperation('backupExistingSchema');
        $this->connection->insert('bak_'.$this->indexTableName, ['a_column' => 'backup']);
        $this->assertSame([1, 2], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT id FROM bak_'.$this->indexTableName.' ORDER BY id')));

        // Recreating the original schema must not collide with the backup's indexes or sequence.
        $this->schemaManager->createTable($table);
        $this->connection->insert($this->indexTableName, ['a_column' => 'reinstalled']);
        $this->executeSchemaOperation('backupExistingSchema');
        $this->connection->insert('bak_'.$this->indexTableName, ['a_column' => 'new backup']);
        $this->assertSame(['reinstalled', 'new backup'], $this->connection->fetchFirstColumn('SELECT a_column FROM bak_'.$this->indexTableName.' ORDER BY id'));
    }

    #[DataProvider('sequenceOwnership')]
    public function testPostgreSqlDropRemovesSequencesAfterTables(bool $owned): void
    {
        if (!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $this->markTestSkipped('PostgreSQL sequence dependencies.');
        }

        if (!$owned) {
            $this->connection->executeStatement('ALTER SEQUENCE '.$this->indexTableName.'_id_seq OWNED BY NONE');
        }

        $table = $this->schemaManager->introspectTable($this->indexTableName);
        $this->executeSchemaOperation('dropExistingSchema');
        $this->schemaManager->createTable($table);
        $this->connection->insert($this->indexTableName, ['a_column' => 'reinstalled']);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM '.$this->indexTableName));
    }

    public static function sequenceOwnership(): iterable
    {
        yield 'serial sequence owned by column' => [true];
        yield 'standalone Doctrine sequence' => [false];
    }

    public function testPostgreSqlCreatesMissingDatabaseUsingMaintenanceConnection(): void
    {
        if (!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $this->markTestSkipped('PostgreSQL requires a maintenance database for CREATE DATABASE.');
        }

        $params           = $this->dbParams;
        $params['dbname'] = 'mautic_install_'.bin2hex(random_bytes(6));
        $helper           = new SchemaHelper($params);
        $target           = DriverManager::getConnection($params);

        try {
            $helper->testConnection();
            $helper->validateDatabaseVersion();
            $this->assertTrue($helper->createDatabase());
            $target->connect();
            $this->assertSame($params['dbname'], $target->fetchOne('SELECT current_database()'));
            $this->assertTrue($helper->createDatabase());
        } finally {
            $target->close();
            $this->connection->executeStatement('DROP DATABASE IF EXISTS '.$this->connection->quoteIdentifier($params['dbname']));
        }
    }

    public function testPointInsightMigrationCreatesUsableTable(): void
    {
        $prefix     = 'migration_test_';
        $categories = new Table($prefix.'categories');
        $categories->addColumn('id', 'integer', ['unsigned' => true]);
        $categories->setPrimaryKey(['id']);
        $this->schemaManager->createTable($categories);

        try {
            $migration = new Version20260616075543($this->connection, new NullLogger());
            $migration->setPrefix($prefix);
            $migration->up($this->schemaManager->introspectSchema());
            foreach ($migration->getSql() as $query) {
                $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            }
            $this->connection->insert($prefix.'point_insights', [
                'name' => 'Insight', 'is_published' => 1, 'insight_type' => 'compare_point_groups',
                'insight_action' => 'set_custom_field', 'point_groups' => '[1,2]',
            ]);
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM '.$prefix.'point_insights'));
            $this->assertCount(1, $this->schemaManager->listTableForeignKeys($prefix.'point_insights'));
        } finally {
            if ($this->schemaManager->tablesExist([$prefix.'point_insights'])) {
                $this->schemaManager->dropTable($prefix.'point_insights');
            }
            $this->schemaManager->dropTable($prefix.'categories');
        }
    }

    public function testFormLimitMigrationBackfillsHistoricalSubmissions(): void
    {
        $prefix = 'migration_test_';
        $forms = new Table($prefix.'forms');
        $forms->addColumn('id', 'integer');
        $forms->setPrimaryKey(['id']);
        $submissions = new Table($prefix.'form_submissions');
        $submissions->addColumn('form_id', 'integer');
        $this->schemaManager->createTable($forms);
        $this->schemaManager->createTable($submissions);

        try {
            $this->connection->insert($prefix.'forms', ['id' => 1]);
            $this->connection->insert($prefix.'forms', ['id' => 2]);
            $this->connection->insert($prefix.'form_submissions', ['form_id' => 1]);
            $this->connection->insert($prefix.'form_submissions', ['form_id' => 1]);
            $migration = new Version20260604000000($this->connection, new NullLogger());
            $migration->setPrefix($prefix);
            $migration->up($this->schemaManager->introspectSchema());
            foreach ($migration->getSql() as $query) {
                $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            }
            $this->assertSame([2, 0], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT submission_count FROM '.$prefix.'forms ORDER BY id')));
            $this->assertTrue($this->schemaManager->introspectTable($prefix.'forms')->hasColumn('submission_limit_message'));
        } finally {
            $this->schemaManager->dropTable($prefix.'form_submissions');
            $this->schemaManager->dropTable($prefix.'forms');
        }
    }

    public function testHistoricalColumnMigrationsPreserveExistingData(): void
    {
        $definitions = [
            'campaign_events' => ['channel_id' => ['integer', ['notnull' => false]]],
            'sms_messages' => [],
            'push_notifications' => [],
            'webhook_queue' => ['payload' => ['text', []]],
            'campaigns' => ['allow_restart' => ['integer', ['default' => 0]]],
            'emails' => ['publish_up' => ['datetime', ['notnull' => false]], 'read_count' => ['integer', ['default' => 0]]],
            'email_stats' => ['email_id' => ['integer', []], 'is_read' => ['boolean', []]],
            'email_copies' => [],
            'lead_lists' => [],
            'pages' => [],
            'lead_fields' => ['alias' => ['string', []], 'type' => ['string', []]],
            'monitoring' => [],
        ];
        foreach ($definitions as $name => $columns) {
            $table = new Table('migration_test_'.$name);
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
            $table->setPrimaryKey(['id']);
            foreach ($columns as $column => [$type, $options]) {
                $table->addColumn($column, $type, $options);
            }
            $this->schemaManager->createTable($table);
        }

        try {
            $this->connection->insert('migration_test_campaign_events', ['id' => 1, 'channel_id' => 123]);
            $this->connection->insert('migration_test_campaign_events', ['id' => 2]);
            $this->connection->insert('migration_test_sms_messages', ['id' => 1]);
            $this->connection->insert('migration_test_webhook_queue', ['payload' => 'preserved']);
            $this->connection->insert('migration_test_campaigns', ['allow_restart' => 1]);
            $this->connection->insert('migration_test_emails', ['publish_up' => '2020-01-01 00:00:00']);
            $this->connection->insert('migration_test_emails', ['publish_up' => null]);
            $this->connection->insert('migration_test_email_stats', ['email_id' => 1, 'is_read' => 1]);
            $this->connection->insert('migration_test_email_stats', ['email_id' => 1, 'is_read' => 0]);
            $this->connection->insert('migration_test_pages', ['id' => 1]);
            $this->connection->insert('migration_test_lead_fields', ['alias' => 'email', 'type' => 'email']);
            $this->connection->insert('migration_test_monitoring', ['id' => 1]);

            $migration = new Migrations\Version20190524124819($this->connection, new NullLogger());
            $indexHelper = $this->createStub(IndexHelper::class);
            $indexHelper->method('getIndexedColumnNames')->willReturn(['email']);
            $container = $this->createStub(ContainerInterface::class);
            $container->method('get')->willReturn($indexHelper);
            $migration->setContainer($container);
            $this->executeMigration($migration);

            foreach ([
                Migrations\Version20200513162918::class,
                Migrations\Version20201007003589::class,
                Migrations\Version20210115065034::class,
                Migrations\Version20210330075323::class,
                Migrations\Version20211110070503::class,
                Migrations\Version20220223072252::class,
                Migrations\Version20221014081742::class,
                Migrations\Version20230131133732::class,
                Migrations\Version20230301051300::class,
                Migrations\Version20230927055621::class,
                Migrations\Version20240320081612::class,
                Migrations\Version20240416112112::class,
                Migrations\Version20240607092418::class,
                Migrations\Version20240708153845::class,
                Migrations\Version20250512114846::class,
                Migrations\Version20251204081531::class,
            ] as $class) {
                $this->executeMigration(new $class($this->connection, new NullLogger()));
            }

            $this->assertSame('123', (string) $this->connection->fetchOne('SELECT channel_id FROM migration_test_campaign_events WHERE id = 1'));
            $this->assertSame('preserved', $this->connection->fetchOne('SELECT payload FROM migration_test_webhook_queue'));
            $this->assertSame([], json_decode($this->connection->fetchOne('SELECT media FROM migration_test_sms_messages'), true));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_campaigns WHERE allow_restart = TRUE AND version = 1'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_emails WHERE continue_sending = TRUE'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_pages WHERE public_preview = TRUE'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_lead_fields WHERE is_index = TRUE AND column_is_not_removed = FALSE AND char_length_limit = 255'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT read_count FROM migration_test_emails WHERE id = 1'));
            $this->assertSame(36, strlen($this->connection->fetchOne('SELECT uuid FROM migration_test_monitoring')));
            $this->connection->update('migration_test_campaign_events', ['redirect_event_id' => 1], ['id' => 2]);
            $this->connection->delete('migration_test_campaign_events', ['id' => 1]);
            $this->assertNull($this->connection->fetchOne('SELECT redirect_event_id FROM migration_test_campaign_events WHERE id = 2'));
            $this->executeMigration(new Migrations\Version20250512114846($this->connection, new NullLogger()), 'down');
            foreach ([Migrations\Version20250828070131::class, Migrations\Version20250923135527::class] as $class) {
                $this->executeMigration(new $class($this->connection, new NullLogger()));
                $this->executeMigration(new $class($this->connection, new NullLogger()), 'down');
            }
            $this->assertFalse($this->schemaManager->introspectTable('migration_test_sms_messages')->hasColumn('translation_parent_id'));
            $this->assertFalse($this->schemaManager->introspectTable('migration_test_push_notifications')->hasColumn('translation_parent_id'));
            $this->assertFalse($this->schemaManager->introspectTable('migration_test_campaign_events')->hasColumn('redirect_event_id'));
            if (DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
                $this->connection->executeStatement("SELECT setval('migration_test_webhook_queue_id_seq', 2147483647)");
                $this->connection->insert('migration_test_webhook_queue', ['payload' => 'beyond integer range']);
                $this->assertSame('2147483648', (string) $this->connection->fetchOne('SELECT MAX(id) FROM migration_test_webhook_queue'));
            }
        } finally {
            foreach (array_reverse(array_keys($definitions)) as $name) {
                $this->schemaManager->dropTable('migration_test_'.$name);
            }
        }
    }

    public function testHistoricalCreateTableMigrationsCreateUsableTables(): void
    {
        $created = [];
        foreach (['emails', 'pages', 'users', 'lead_lists'] as $name) {
            $table = new Table('migration_test_'.$name);
            $table->addColumn('id', 'integer', ['unsigned' => true]);
            $table->setPrimaryKey(['id']);
            $this->schemaManager->createTable($table);
            $created[] = 'migration_test_'.$name;
            $this->connection->insert('migration_test_'.$name, ['id' => 1]);
        }

        try {
            foreach ([
                'emails_draft' => Migrations\Version20201019100000::class,
                'pages_draft' => Migrations\Version20201029001005::class,
                'contact_export_scheduler' => Migrations\Version20220429091934::class,
                'email_list_excluded' => Migrations\Version20240226114528::class,
            ] as $table => $class) {
                $this->executeMigration(new $class($this->connection, new NullLogger()));
                $created[] = 'migration_test_'.$table;
            }
            $this->connection->insert('migration_test_emails_draft', ['email_id' => 1]);
            $this->connection->insert('migration_test_pages_draft', ['page_id' => 1]);
            $this->connection->insert('migration_test_contact_export_scheduler', ['user_id' => 1, 'scheduled_datetime' => '2020-01-01 00:00:00']);
            $this->connection->insert('migration_test_email_list_excluded', ['email_id' => 1, 'leadlist_id' => 1]);
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_emails_draft WHERE id = 1 AND public_preview = TRUE'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_pages_draft WHERE id = 1 AND public_preview = TRUE'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM migration_test_contact_export_scheduler'));
            $this->connection->delete('migration_test_lead_lists', ['id' => 1]);
            $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_test_email_list_excluded'));
        } finally {
            foreach (array_reverse($created) as $name) {
                $this->schemaManager->dropTable($name);
            }
        }
    }

    private function executeMigration(AbstractMauticMigration $migration, string $direction = 'up'): void
    {
        $tables = [];
        foreach ($this->schemaManager->listTableNames() as $name) {
            if (str_starts_with($name, 'migration_test_')) {
                $tables[] = $this->schemaManager->introspectTable($name);
            }
        }
        $source = new Schema($tables);
        $target = clone $source;
        $migration->setPrefix('migration_test_');
        $migration->{'pre'.ucfirst($direction)}($target);
        $migration->{$direction}($target);
        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
        $diff = $this->schemaManager->createComparator()->compareSchemas($source, $target);
        foreach ($this->connection->getDatabasePlatform()->getAlterSchemaSQL($diff) as $query) {
            $this->connection->executeStatement($query);
        }
    }

    private function executeSchemaOperation(string $operation): void
    {
        $schemaHelper = new SchemaHelper($this->dbParams);
        $reflection   = new \ReflectionClass(SchemaHelper::class);
        $reflection->getProperty('platform')->setValue($schemaHelper, $this->connection->getDatabasePlatform());
        $tables       = [$this->indexTableName, 'bak_'.$this->indexTableName];
        $mauticTables = [$this->indexTableName => 'bak_'.$this->indexTableName];
        $tables       = array_values(array_filter($tables, fn (string $table) => $this->schemaManager->tablesExist([$table])));
        $sql          = $reflection->getMethod($operation)->invokeArgs($schemaHelper, [$tables, $mauticTables, 'bak_']);

        foreach ($sql as $query) {
            $this->connection->executeStatement($query);
        }
    }
}
