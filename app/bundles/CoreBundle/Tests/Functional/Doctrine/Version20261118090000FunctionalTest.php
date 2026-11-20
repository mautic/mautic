<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Schema\Schema;
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

        $schema = new Schema();
        $table  = $schema->createTable($this->tableName);
        $table->addColumn('id', 'integer')->setUnsigned(true);
        $table->addColumn('preferences', 'text')->setNotnull(false);
        $table->setPrimaryKey(['id']);

        $migration = new Version20261118090000($this->connection, new NullLogger());
        $migration->setPrefix(self::getContainer()->getParameter('mautic.db_table_prefix').'test_migration_');
        $migration->up($schema);

        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement());
        }
        $this->connection->executeStatement("ALTER TABLE {$this->tableName} MODIFY preferences LONGTEXT NOT NULL");

        $nullPreferences  = $this->connection->fetchOne("SELECT preferences FROM {$this->tableName} WHERE id = 1");
        $savedPreferences = $this->connection->fetchOne("SELECT preferences FROM {$this->tableName} WHERE id = 2");

        $this->assertSame([], unserialize((string) $nullPreferences, ['allowed_classes' => false]));
        $this->assertSame(['theme' => 'dark'], unserialize((string) $savedPreferences, ['allowed_classes' => false]));
        $this->assertTrue($schema->getTable($this->tableName)->getColumn('preferences')->getNotnull());

        try {
            $this->connection->insert($this->tableName, ['id' => 3, 'preferences' => null]);
            $this->fail('The migrated column must reject NULL preferences.');
        } catch (\Throwable $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}
