<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\Migrations\Version20260604000000;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FormLimitMigrationRecoveryTest extends TestCase
{
    private Connection $connection;
    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL historical form-limit recovery.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => $_ENV['DB_DRIVER'], 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'form_limit_recovery_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
    }

    protected function tearDown(): void
    {
        if (isset($this->namespace)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    /**
     * @param list<string> $existingColumns
     */
    #[DataProvider('columnCombinations')]
    public function testRecoversIncompleteSchemaAndPreservesCompleteSchema(array $existingColumns): void
    {
        $schema = new Schema();
        $forms  = $schema->createTable('fixture_forms');
        $forms->addColumn('id', 'integer');
        $forms->setPrimaryKey(['id']);
        foreach ($existingColumns as $column) {
            $forms->addColumn($column, 'submission_limit_message' === $column ? 'text' : 'integer', ['notnull' => false]);
        }
        $submissions = $schema->createTable('fixture_form_submissions');
        $submissions->addColumn('id', 'integer');
        $submissions->addColumn('form_id', 'integer');
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        $values = array_intersect_key(['submission_limit' => 7, 'submission_limit_message' => 'Existing message', 'submission_count' => 0], array_flip($existingColumns));
        foreach ([1, 2] as $formId) {
            if (2 === $formId && array_key_exists('submission_count', $values)) {
                $values['submission_count'] = 99;
            }
            $this->connection->insert('fixture_forms', ['id' => $formId] + $values);
        }
        $this->connection->insert('fixture_form_submissions', ['id' => 1, 'form_id' => 1]);
        $this->connection->insert('fixture_form_submissions', ['id' => 2, 'form_id' => 1]);
        $migration = new Version20260604000000($this->connection, new NullLogger());
        $migration->setPrefix('fixture_');
        if (3 === count($existingColumns)) {
            try {
                $migration->preUp($schema);
                self::fail('Complete schema must skip without recalculating its existing counters.');
            } catch (SkipMigration) {
                $this->assertSame([0, 99], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT submission_count FROM fixture_forms ORDER BY id')));
            }

            return;
        }

        $migration->preUp($schema);
        $migration->up($schema);
        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
        $actual = $this->connection->createSchemaManager()->introspectTable('fixture_forms');
        foreach (['submission_limit', 'submission_limit_message', 'submission_count'] as $column) {
            $this->assertTrue($actual->hasColumn($column));
        }
        $this->assertSame([2, 0], array_map(intval(...), $this->connection->fetchFirstColumn('SELECT submission_count FROM fixture_forms ORDER BY id')));
        foreach (['submission_limit' => 7, 'submission_limit_message' => 'Existing message'] as $column => $value) {
            $expected = in_array($column, $existingColumns, true) ? $value : null;
            $this->assertEquals([$expected, $expected], $this->connection->fetchFirstColumn('SELECT '.$column.' FROM fixture_forms ORDER BY id'));
        }
    }

    public static function columnCombinations(): iterable
    {
        $columns = ['submission_limit', 'submission_limit_message', 'submission_count'];
        for ($mask = 0; $mask < 8; ++$mask) {
            $existing = [];
            foreach ($columns as $index => $column) {
                if (0 !== ($mask & (1 << $index))) {
                    $existing[] = $column;
                }
            }
            yield (string) $mask => [$existing];
        }
    }
}
