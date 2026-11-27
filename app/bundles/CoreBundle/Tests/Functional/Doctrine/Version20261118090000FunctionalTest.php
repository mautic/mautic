<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\Migrations\Version20261118090000;
use Psr\Log\NullLogger;

final class Version20261118090000FunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $tableName;

    protected function setUp(): void
    {
        parent::setUp();

        $tablePrefix   = self::getContainer()->getParameter('mautic.db_table_prefix');
        $this->tableName = $tablePrefix.'test_migration_users';
        $this->connection->executeStatement("CREATE TABLE {$this->tableName} (
            id INT UNSIGNED NOT NULL,
            preferences LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB");
    }

    protected function beforeTearDown(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.$this->tableName);
    }

    public function testBackfillPreservesValuesAndTightensTheColumn(): void
    {
        $savedPreferences = serialize(['theme' => 'dark']);
        $this->connection->insert($this->tableName, ['id' => 1, 'preferences' => null]);
        $this->connection->insert($this->tableName, ['id' => 2, 'preferences' => $savedPreferences]);

        $beforeSchema = $this->createUserSchema();
        $schema       = $this->createUserSchema();

        $migration = new Version20261118090000($this->connection, new NullLogger());
        $migration->setPrefix(self::getContainer()->getParameter('mautic.db_table_prefix').'test_migration_');
        $migration->up($schema);

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement());
        }

        $schemaDiff = (new Comparator())->compareSchemas($beforeSchema, $schema);
        foreach ($this->connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff) as $query) {
            $this->connection->executeStatement($query);
        }

        $nullPreferences  = $this->connection->fetchOne("SELECT preferences FROM {$this->tableName} WHERE id = 1");
        $savedPreferences = $this->connection->fetchOne("SELECT preferences FROM {$this->tableName} WHERE id = 2");

        $this->assertSame([], Serializer::decode((string) $nullPreferences));
        $this->assertSame(['theme' => 'dark'], Serializer::decode((string) $savedPreferences));
        $actualTable = $this->connection->createSchemaManager()->introspectTable($this->tableName);
        $this->assertTrue($actualTable->getColumn('preferences')->getNotnull());

        $this->expectException(NotNullConstraintViolationException::class);
        $this->connection->insert($this->tableName, ['id' => 3, 'preferences' => null]);
    }

    private function createUserSchema(): Schema
    {
        $schema = new Schema();
        $table  = $schema->createTable($this->tableName);
        $table->addColumn('id', 'integer')->setUnsigned(true);
        $table->addColumn('preferences', 'text')->setNotnull(false);
        $table->setPrimaryKey(['id']);

        return $schema;
    }
}
