<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\InlineParameterFormatter;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\Version;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\Migrations\Version20190326190241;
use Mautic\Migrations\Version20231206152313;
use Mautic\Migrations\Version20250127092203;
use Mautic\Migrations\Version20250409150450;
use Mautic\Migrations\Version20250424092044;
use Mautic\Migrations\Version20250828070131;
use Mautic\Migrations\Version20250923135527;
use Mautic\Migrations\Version20260120130000;
use Mautic\Migrations\Version20260616075543;
use Mautic\Migrations\Version20261015160000;
use Mautic\Migrations\Version20261015170000;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch;

final class PostgreSqlUpgradeLifecycleTest extends TestCase
{
    private Connection $connection;

    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL upgrade lifecycle.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql', 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'upgrade_lifecycle_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
        $this->connection->getConfiguration()->setSchemaAssetsFilter(static fn (string|AbstractAsset $asset): bool => !str_contains(is_string($asset) ? $asset : $asset->getName(), '.'));
        if (!Type::hasType(GeneratedType::GENERATED)) {
            Type::addType(GeneratedType::GENERATED, GeneratedType::class);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    public function testPopulatedUpgradeUsesExecutorAndPreservesForeignKeysAndRows(): void
    {
        $schema = new Schema();
        foreach (['campaigns', 'projects', 'lead_lists', 'emails', 'leads', 'campaign_events'] as $name) {
            $table = $schema->createTable('fixture_'.$name);
            $table->addColumn('id', 'integer');
            $table->setPrimaryKey(['id']);
        }
        $stats = $schema->createTable('fixture_email_stats');
        $stats->addColumn('id', 'integer');
        $stats->setPrimaryKey(['id']);
        $stats->addColumn('lead_id', 'integer');
        $stats->addColumn('email_id', 'integer');
        $stats->addColumn('date_sent', 'datetime', ['notnull' => false]);
        $stats->addColumn('is_read', 'boolean', ['default' => false]);
        $statsMigration = new Version20231206152313($this->connection, new NullLogger());
        $statsMigration->setPrefix('fixture_');
        $oldIndexes = [];
        foreach (['lead_id' => 'getOldLeadIndexName', 'email_id' => 'getOldEmailIndexName'] as $column => $method) {
            $oldIndexes[] = $index = (new \ReflectionMethod($statsMigration, $method))->invoke($statsMigration);
            $stats->addIndex([$column], $index);
            $stats->addForeignKeyConstraint('fixture_'.('lead_id' === $column ? 'leads' : 'emails'), [$column], ['id']);
        }
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        foreach (['campaigns', 'projects', 'lead_lists', 'emails', 'leads', 'campaign_events'] as $name) {
            $this->connection->insert('fixture_'.$name, ['id' => 1]);
        }
        $this->connection->insert('fixture_email_stats', ['id' => 1, 'lead_id' => 1, 'email_id' => 1]);

        $this->execute(new Version20190326190241($this->connection, new NullLogger()));
        $this->execute($statsMigration);
        $this->execute(new Version20250409150450($this->connection, new NullLogger()));
        $this->execute(new Version20250424092044($this->connection, new NullLogger()));

        $manager = $this->connection->createSchemaManager();
        $this->assertSame(0, $this->connection->fetchOne('SELECT failed_count FROM fixture_campaign_events WHERE id = 1'));
        $this->assertNull($manager->introspectTable('fixture_campaign_events')->getColumn('failed_count')->getDefault());
        $this->assertSame(1, $this->connection->fetchOne('SELECT id FROM fixture_email_stats'));
        $table = $manager->introspectTable('fixture_email_stats');
        $this->assertTrue($table->hasIndex('fixture_stat_email_lead_id_date_sent'));
        $this->assertTrue($table->hasIndex('fixture_stat_email_email_id_is_read'));
        foreach ($oldIndexes as $index) {
            $this->assertArrayNotHasKey(strtolower($index), $manager->listTableIndexes('fixture_email_stats'));
        }
        $this->assertCount(2, $table->getForeignKeys());
        foreach (['lead_list_projects_xref' => 'leadlist_id', 'campaign_projects_xref' => 'campaign_id'] as $name => $column) {
            $this->connection->insert('fixture_'.$name, [$column => 1, 'project_id' => 1]);
            $this->assertCount(2, $manager->introspectTable('fixture_'.$name)->getForeignKeys());
        }
        $this->connection->delete('fixture_projects', ['id' => 1]);
        $this->assertSame(0, $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_lead_list_projects_xref'));
        $this->assertSame(0, $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_campaign_projects_xref'));
    }

    public function testDifferentInstallationPrefixesHaveIndependentMetadataIndexNames(): void
    {
        $schema = new Schema();
        foreach (['fixture_one_', 'fixture_two_'] as $prefix) {
            foreach (['roles', 'categories', 'sms_messages', 'push_notifications'] as $name) {
                $table = $schema->createTable($prefix.$name);
                $table->addColumn('id', 'integer', ['unsigned' => true]);
                $table->setPrimaryKey(['id']);
            }
        }
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        $manager = $this->connection->createSchemaManager();
        foreach (['fixture_one_', 'fixture_two_'] as $prefix) {
            foreach ([Version20250127092203::class, Version20250828070131::class, Version20250923135527::class, Version20260616075543::class] as $class) {
                $this->execute(new $class($this->connection, new NullLogger()), $prefix);
            }
            $indexes = $manager->listTableIndexes($prefix.'user_invites');
            foreach (['UNIQ_USER_INVITES_TOKEN_SELECTOR', 'IDX_USER_INVITES_EMAIL', 'IDX_USER_INVITES_EXPIRATION', 'IDX_USER_INVITES_USED', 'IDX_USER_INVITES_ROLE'] as $name) {
                $this->assertArrayHasKey(strtolower($prefix.$name), $indexes);
            }
            foreach (['sms_messages' => 'translation_parent_id', 'push_notifications' => 'translation_parent_id', 'point_insights' => 'category_id'] as $name => $column) {
                // Doctrine creates implicit association indexes from the prefixed table name.
                $expected = new Table($prefix.$name);
                $expected->addColumn($column, 'integer');
                $expected->addForeignKeyConstraint($prefix.$name, [$column], ['id']);
                $index = array_values($expected->getIndexes())[0];
                $foreignKey = array_values($expected->getForeignKeys())[0];
                $this->assertArrayHasKey(strtolower($index->getName()), $manager->listTableIndexes($prefix.$name));
                $this->assertTrue($manager->introspectTable($prefix.$name)->hasForeignKey($foreignKey->getName()));
            }
        }
        $this->execute(new Version20250828070131($this->connection, new NullLogger()), 'fixture_one_', 'down');
        $this->execute(new Version20250923135527($this->connection, new NullLogger()), 'fixture_one_', 'down');
        $this->assertFalse($manager->introspectTable('fixture_one_sms_messages')->hasColumn('translation_parent_id'));
        $this->assertTrue($manager->introspectTable('fixture_two_sms_messages')->hasColumn('translation_parent_id'));
    }

    public function testTranslationRollbackAlsoHandlesLegacyConstraintAndIndexNames(): void
    {
        $schema = new Schema();
        foreach (['sms_messages' => 'SMS', 'push_notifications' => 'PUSH_NOTIFICATIONS'] as $name => $suffix) {
            $table = $schema->createTable('fixture_'.$name);
            $table->addColumn('id', 'integer', ['unsigned' => true]);
            $table->addColumn('translation_parent_id', 'integer', ['unsigned' => true, 'notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['translation_parent_id'], 'IDX_'.$suffix.'_TRANSLATION_PARENT');
            $table->addForeignKeyConstraint('fixture_'.$name, ['translation_parent_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_'.$suffix.'_TRANSLATION_PARENT');
        }
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        foreach ([Version20250828070131::class, Version20250923135527::class] as $class) {
            $this->execute(new $class($this->connection, new NullLogger()), 'fixture_', 'down');
        }
        $manager = $this->connection->createSchemaManager();
        $this->assertFalse($manager->introspectTable('fixture_sms_messages')->hasColumn('translation_parent_id'));
        $this->assertFalse($manager->introspectTable('fixture_push_notifications')->hasColumn('translation_parent_id'));
    }

    #[DataProvider('legacyPrefixes')]
    public function testLegacyNamesAreRenamedWithoutRebuildingIndexesOrAffectingAnotherInstallation(string $legacyPrefix): void
    {
        $schema = new Schema();
        $inviteIndexes = [
            'UNIQ_USER_INVITES_TOKEN_SELECTOR' => 'token_selector', 'IDX_USER_INVITES_EMAIL' => 'email',
            'IDX_USER_INVITES_EXPIRATION' => 'expiration', 'IDX_USER_INVITES_USED' => 'used', 'IDX_USER_INVITES_ROLE' => 'role_id',
        ];
        $associations = [
            'sms_messages' => ['translation_parent_id', 'IDX_SMS_TRANSLATION_PARENT', 'FK_SMS_TRANSLATION_PARENT'],
            'push_notifications' => ['translation_parent_id', 'IDX_PUSH_NOTIFICATIONS_TRANSLATION_PARENT', 'FK_PUSH_NOTIFICATIONS_TRANSLATION_PARENT'],
            'point_insights' => ['category_id', 'IDX_9894AA2612469DE2', 'FK_9894AA2612469DE2'],
        ];
        foreach ([$legacyPrefix, 'fixture_current_'] as $prefix) {
            foreach (['roles', 'categories'] as $name) {
                $table = $schema->createTable($prefix.$name);
                $table->addColumn('id', 'integer');
                $table->setPrimaryKey(['id']);
            }
            $table = $schema->createTable($prefix.'user_invites');
            $table->addColumn('id', 'integer');
            $table->setPrimaryKey(['id']);
            $table->addColumn('token_selector', 'string', ['length' => 32]);
            $table->addColumn('email', 'string', ['length' => 191]);
            $table->addColumn('expiration', 'datetime');
            $table->addColumn('used', 'boolean', ['default' => false]);
            $table->addColumn('role_id', 'integer');
            foreach ($inviteIndexes as $name => $column) {
                $indexName = $prefix === $legacyPrefix ? $name : $prefix.$name;
                if ('token_selector' === $column) {
                    $table->addUniqueIndex([$column], $indexName);
                } else {
                    $table->addIndex([$column], $indexName);
                }
            }
            $table->addForeignKeyConstraint($prefix.'roles', ['role_id'], ['id']);
            foreach ($associations as $name => [$column, $index, $foreignKey]) {
                $table = $schema->createTable($prefix.$name);
                $table->addColumn('id', 'integer');
                $table->setPrimaryKey(['id']);
                $table->addColumn($column, 'integer', ['notnull' => false]);
                if ($prefix === $legacyPrefix) {
                    $table->addIndex([$column], $index);
                }
                $table->addForeignKeyConstraint($prefix.('point_insights' === $name ? 'categories' : $name), [$column], ['id'], ['onDelete' => 'CASCADE'], $prefix === $legacyPrefix ? $foreignKey : null);
            }
        }
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        foreach ([$legacyPrefix, 'fixture_current_'] as $prefix) {
            $this->connection->insert($prefix.'roles', ['id' => 1]);
            $this->connection->insert($prefix.'categories', ['id' => 1]);
            $this->connection->insert($prefix.'user_invites', ['id' => 1, 'token_selector' => 'preserved', 'email' => 'preserved@example.com', 'expiration' => '2030-01-01 00:00:00', 'role_id' => 1]);
            foreach ($associations as $name => [$column]) {
                $this->connection->insert($prefix.$name, ['id' => 1]);
            }
        }
        $indexesBefore = $this->connection->fetchFirstColumn("SELECT oid FROM pg_class WHERE relnamespace = current_schema()::regnamespace AND relkind = 'i' ORDER BY oid");
        $constraintsBefore = $this->connection->fetchFirstColumn('SELECT oid FROM pg_constraint WHERE connamespace = current_schema()::regnamespace ORDER BY oid');
        $migration = new Version20261015170000($this->connection, new NullLogger());
        $this->execute($migration, $legacyPrefix);
        $this->assertNotEmpty($migration->getSql());
        $this->execute(new Version20261015170000($this->connection, new NullLogger()), $legacyPrefix, 'up', true);
        $this->assertSame($indexesBefore, $this->connection->fetchFirstColumn("SELECT oid FROM pg_class WHERE relnamespace = current_schema()::regnamespace AND relkind = 'i' ORDER BY oid"));
        $this->assertSame($constraintsBefore, $this->connection->fetchFirstColumn('SELECT oid FROM pg_constraint WHERE connamespace = current_schema()::regnamespace ORDER BY oid'));
        $manager = $this->connection->createSchemaManager();
        foreach ([$legacyPrefix, 'fixture_current_'] as $prefix) {
            $this->assertSame('preserved@example.com', $this->connection->fetchOne('SELECT email FROM '.$prefix.'user_invites WHERE id = 1'));
            foreach ($inviteIndexes as $name => $column) {
                $this->assertArrayHasKey(strtolower($prefix.$name), $manager->listTableIndexes($prefix.'user_invites'));
            }
            foreach ($associations as $name => [$column]) {
                $expected = new Table($prefix.$name);
                $expected->addColumn($column, 'integer');
                $expected->addForeignKeyConstraint($prefix.$name, [$column], ['id']);
                $index = array_values($expected->getIndexes())[0];
                $foreignKey = array_values($expected->getForeignKeys())[0];
                $this->assertArrayHasKey(strtolower($index->getName()), $manager->listTableIndexes($prefix.$name));
                $this->assertTrue($manager->introspectTable($prefix.$name)->hasForeignKey($foreignKey->getName()));
                $this->assertSame(1, $this->connection->fetchOne('SELECT id FROM '.$prefix.$name));
            }
        }
    }

    public static function legacyPrefixes(): iterable
    {
        yield 'default installation' => [''];
        yield 'custom prefix installation' => ['fixture_legacy_'];
    }

    /**
     * @param class-string<AbstractMauticMigration> $migrationClass
     */
    #[DataProvider('compatibilityMigrations')]
    public function testCompatibilityFunctionsExecuteThroughDoctrineExecutor(string $migrationClass): void
    {
        $this->execute(new $migrationClass($this->connection, new NullLogger()));

        $this->assertSame(count(array_filter(PostgreSqlCompatibilityFunctions::getSql(), static fn (string $sql): bool => str_starts_with($sql, 'CREATE OR REPLACE FUNCTION'))), (int) $this->connection->fetchOne('SELECT COUNT(*) FROM pg_proc WHERE pronamespace = ?::regnamespace', [$this->namespace]));
        $this->assertSame('www.mautic', $this->connection->fetchOne("SELECT substring_index('www.mautic.org', '.', 2)"));
        $this->assertSame('mautic.org', $this->connection->fetchOne("SELECT substring_index('www.mautic.org', '.', -2)"));
        $this->assertSame('', $this->connection->fetchOne("SELECT substring_index('www.mautic.org', '.', 0)"));
    }

    /**
     * @return iterable<string, array{class-string<AbstractMauticMigration>}>
     */
    public static function compatibilityMigrations(): iterable
    {
        yield 'original compatibility migration' => [Version20260120130000::class];
        yield 'refresh existing installations' => [Version20261015160000::class];
    }

    private function execute(AbstractMauticMigration $migration, string $prefix = 'fixture_', string $direction = 'up', bool $skipped = false): void
    {
        $migration->setPrefix($prefix);
        $executor = new DbalExecutor(
            $this->createStub(MetadataStorage::class),
            new EventDispatcher($this->connection, new EventManager()),
            $this->connection,
            new DBALSchemaDiffProvider($this->connection->createSchemaManager(), $this->connection->getDatabasePlatform()),
            new NullLogger(),
            new InlineParameterFormatter($this->connection),
            new Stopwatch(),
        );
        $result = $executor->execute(new MigrationPlan(new Version($migration::class), $migration, $direction), new MigratorConfiguration());
        $this->assertSame($skipped, $result->isSkipped());
        $this->assertFalse($result->hasError());
    }
}
