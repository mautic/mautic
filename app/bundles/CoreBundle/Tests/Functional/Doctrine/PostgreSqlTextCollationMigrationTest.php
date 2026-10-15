<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\Migrations\Version20261015180000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PostgreSqlTextCollationMigrationTest extends TestCase
{
    private Connection $connection;

    private string $schemaName;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL collation migration.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql', 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->schemaName = 'text_collation_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->connection->quoteIdentifier($this->schemaName));
        $this->connection->executeStatement('SET search_path TO '.$this->connection->quoteIdentifier($this->schemaName).', public');
        $this->connection->executeStatement("CREATE COLLATION mautic_unicode_ci (provider = icu, locale = 'und-u-ks-level1', deterministic = false)");
        $this->connection->getConfiguration()->setSchemaAssetsFilter(static fn (string|AbstractAsset $asset): bool => !str_contains(is_string($asset) ? $asset : $asset->getName(), '.'));
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->connection->quoteIdentifier($this->schemaName).' CASCADE');
            $this->connection->close();
        }
    }

    public function testMigrationConvertsOwnedAndDynamicTextColumnsAndRebuildsGeneratedColumns(): void
    {
        $this->connection->executeStatement('CREATE TABLE fixture_forms (id integer PRIMARY KEY, alias varchar(40) NOT NULL)');
        $this->connection->executeStatement("INSERT INTO fixture_forms VALUES (1, 'demo')");
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE fixture_users (
                id integer PRIMARY KEY,
                username varchar(40) NOT NULL UNIQUE,
                body text,
                body_copy text GENERATED ALWAYS AS (body || '!') STORED
            )
            SQL);
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE fixture_leads (
                email varchar(255),
                generated_email_domain varchar(255) GENERATED ALWAYS AS (SUBSTRING(email, POSITION('@' IN email) + 1)) STORED
            )
            SQL);
        $this->connection->executeStatement("INSERT INTO fixture_leads (email) VALUES ('user@example.test')");
        $this->connection->executeStatement("INSERT INTO fixture_users VALUES (1, 'Alice', 'Hello', DEFAULT)");
        $this->connection->executeStatement('CREATE TABLE fixture_form_results_1_demo (submission_id integer, name text)');
        $this->connection->executeStatement('CREATE TABLE unrelated (name text)');

        $migration = $this->migration(['fixture_users', 'fixture_forms', 'fixture_leads']);
        $migration->up(new Schema());
        $this->runMigration($migration);

        $this->assertSame('Hello!', $this->connection->fetchOne('SELECT body_copy FROM fixture_users WHERE id = 1'));
        $this->assertSame('example.test', $this->connection->fetchOne('SELECT generated_email_domain FROM fixture_leads'));
        foreach ([['fixture_users', 'username'], ['fixture_users', 'body'], ['fixture_users', 'body_copy'], ['fixture_leads', 'email'], ['fixture_leads', 'generated_email_domain'], ['fixture_form_results_1_demo', 'name']] as [$table, $column]) {
            $this->assertSame('mautic_unicode_ci', $this->columnCollation($table, $column));
        }
        $this->assertNotSame('mautic_unicode_ci', $this->columnCollation('unrelated', 'name'));
        $this->assertTrue((bool) $this->connection->fetchOne("SELECT username = 'alice' FROM fixture_users WHERE id = 1"));

        $repeat = $this->migration(['fixture_users', 'fixture_forms', 'fixture_leads']);
        $this->expectException(SkipMigration::class);
        $repeat->up(new Schema());
    }

    public function testEmptyPrefixUsesKnownMetadataAndFormTablesOnly(): void
    {
        $this->connection->executeStatement('CREATE TABLE users (username varchar(40))');
        $this->connection->executeStatement('CREATE TABLE forms (id integer PRIMARY KEY, alias varchar(40) NOT NULL)');
        $this->connection->executeStatement("INSERT INTO forms VALUES (2, 'contact')");
        $this->connection->executeStatement('CREATE TABLE form_results_2_contact (result text)');
        $this->connection->executeStatement('CREATE TABLE unrelated (name text)');

        $migration = $this->migration(['users', 'forms']);
        $migration->setPrefix('');
        $migration->up(new Schema());
        $this->runMigration($migration);

        $this->assertSame('mautic_unicode_ci', $this->columnCollation('users', 'username'));
        $this->assertSame('mautic_unicode_ci', $this->columnCollation('form_results_2_contact', 'result'));
        $this->assertNotSame('mautic_unicode_ci', $this->columnCollation('unrelated', 'name'));
    }

    public function testMigrationPreservesAndRebuildsForeignKeysAcrossOwnedTextCollationChanges(): void
    {
        $this->connection->executeStatement('CREATE TABLE fixture_copies (id varchar(36) PRIMARY KEY)');
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE fixture_email_stats (
                id integer PRIMARY KEY,
                copy_id varchar(36) NOT NULL,
                CONSTRAINT fixture_fk_copy FOREIGN KEY (copy_id) REFERENCES fixture_copies (id)
                    ON DELETE CASCADE DEFERRABLE INITIALLY DEFERRED
            )
            SQL);
        $this->connection->executeStatement("COMMENT ON CONSTRAINT fixture_fk_copy ON fixture_email_stats IS 'keep this constraint comment'");
        $this->connection->executeStatement("INSERT INTO fixture_copies VALUES ('copy-1')");
        $this->connection->executeStatement("INSERT INTO fixture_email_stats VALUES (1, 'copy-1')");

        $migration = $this->migration(['fixture_copies', 'fixture_email_stats']);
        $migration->up(new Schema());
        $this->runMigration($migration);

        $this->assertSame('mautic_unicode_ci', $this->columnCollation('fixture_copies', 'id'));
        $this->assertSame('mautic_unicode_ci', $this->columnCollation('fixture_email_stats', 'copy_id'));
        $definition = $this->connection->fetchAssociative(
            "SELECT pg_get_constraintdef(oid, true) AS definition, obj_description(oid, 'pg_constraint') AS comment FROM pg_constraint WHERE conrelid = to_regclass('fixture_email_stats') AND conname = 'fixture_fk_copy'"
        );
        $this->assertStringContainsString('ON DELETE CASCADE', (string) $definition['definition']);
        $this->assertStringContainsString('DEFERRABLE INITIALLY DEFERRED', (string) $definition['definition']);
        $this->assertSame('keep this constraint comment', $definition['comment']);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_email_stats'));

        try {
            $this->connection->executeStatement("INSERT INTO fixture_email_stats VALUES (2, 'missing-copy')");
            $this->fail('The restored foreign key must continue to reject missing references.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_email_stats'));
        }
    }

    public function testUnownedReferencingTableAbortsBeforeChangingOwnedReferencedColumn(): void
    {
        $this->connection->executeStatement('CREATE TABLE fixture_copies (id varchar(36) PRIMARY KEY)');
        $this->connection->executeStatement('CREATE TABLE external_stats (copy_id varchar(36) REFERENCES fixture_copies (id))');
        $this->connection->executeStatement("INSERT INTO fixture_copies VALUES ('copy-1')");
        $this->connection->executeStatement("INSERT INTO external_stats VALUES ('copy-1')");

        $migration = $this->migration(['fixture_copies']);
        try {
            $migration->up(new Schema());
            $this->fail('The migration must not change an owned referenced key when an unowned referencing table depends on it.');
        } catch (\Doctrine\Migrations\Exception\AbortMigration $exception) {
            $this->assertStringContainsString('unowned or nonmigrated column', $exception->getMessage());
            $this->assertNotSame('mautic_unicode_ci', $this->columnCollation('fixture_copies', 'id'));
            $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM external_stats'));
            $this->assertSame(1, (int) $this->connection->fetchOne("SELECT COUNT(*) FROM pg_constraint WHERE conrelid = to_regclass('external_stats') AND conname = 'external_stats_copy_id_fkey'"));
        }
    }

    public function testAlreadyCollatedForeignKeyPeerDoesNotPreventPartialMigration(): void
    {
        if ((int) $this->connection->fetchOne('SHOW server_version_num') >= 180000) {
            $this->markTestSkipped('PostgreSQL 18 rejects mismatched foreign-key collations when the legacy partial state is created.');
        }

        $this->connection->executeStatement('CREATE TABLE fixture_copies (id varchar(36) COLLATE mautic_unicode_ci PRIMARY KEY)');
        $this->connection->executeStatement('CREATE TABLE fixture_email_stats (copy_id varchar(36) REFERENCES fixture_copies (id))');
        $this->connection->executeStatement("INSERT INTO fixture_copies VALUES ('copy-1')");
        $this->connection->executeStatement("INSERT INTO fixture_email_stats VALUES ('copy-1')");

        $migration = $this->migration(['fixture_copies', 'fixture_email_stats']);
        $migration->up(new Schema());
        $this->runMigration($migration);

        $this->assertSame('mautic_unicode_ci', $this->columnCollation('fixture_copies', 'id'));
        $this->assertSame('mautic_unicode_ci', $this->columnCollation('fixture_email_stats', 'copy_id'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_email_stats'));
        $this->assertSame(1, (int) $this->connection->fetchOne("SELECT COUNT(*) FROM pg_constraint WHERE conrelid = to_regclass('fixture_email_stats') AND contype = 'f'"));
    }

    public function testCaseInsensitiveUniqueCollisionRollsBackWithoutDroppingRows(): void
    {
        $this->connection->executeStatement('CREATE TABLE fixture_accounts (username varchar(40) NOT NULL UNIQUE)');
        $this->connection->executeStatement("INSERT INTO fixture_accounts VALUES ('Alice'), ('alice')");

        $migration = $this->migration(['fixture_accounts']);
        $migration->up(new Schema());
        $this->connection->beginTransaction();
        try {
            $this->runMigration($migration);
            $this->connection->commit();
        } catch (UniqueConstraintViolationException $exception) {
            $this->connection->rollBack();
            $this->assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_accounts'));
            $this->assertNotSame('mautic_unicode_ci', $this->columnCollation('fixture_accounts', 'username'));
            $this->assertStringContainsString('duplicate', strtolower($exception->getMessage()));

            return;
        }

        $this->fail('The new collation must reject duplicate values in an existing unique constraint.');
    }

    /** @param string[] $tableNames */
    private function migration(array $tableNames): Version20261015180000
    {
        $migration = new Version20261015180000($this->connection, new NullLogger());
        $migration->setPrefix('fixture_');
        $metadata = array_map(static fn (string $tableName): object => new class($tableName) {
            public function __construct(private readonly string $tableName)
            {
            }

            public function getTableName(): string
            {
                return $this->tableName;
            }

            public function getAssociationMappings(): array
            {
                return [];
            }
        }, $tableNames);
        $entityManager = new class($metadata) {
            public function __construct(private readonly array $metadata)
            {
            }

            public function getMetadataFactory(): object
            {
                return new class($this->metadata) {
                    public function __construct(private readonly array $metadata)
                    {
                    }

                    public function getAllMetadata(): array
                    {
                        return $this->metadata;
                    }
                };
            }
        };
        $container = new ContainerBuilder();
        $container->set('doctrine.orm.entity_manager', $entityManager);
        $container->setParameter('mautic.db_table_prefix', 'fixture_');
        $migration->setContainer($container);

        return $migration;
    }

    private function runMigration(Version20261015180000 $migration): void
    {
        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }

    private function columnCollation(string $table, string $column): ?string
    {
        return $this->connection->fetchOne(
            <<<'SQL'
                SELECT coll.collname
                FROM pg_attribute attribute
                JOIN pg_class relation ON relation.oid = attribute.attrelid
                JOIN pg_collation coll ON coll.oid = attribute.attcollation
                WHERE relation.oid = to_regclass(:table) AND attribute.attname = :column
                SQL,
            ['table' => $table, 'column' => $column]
        ) ?: null;
    }
}
