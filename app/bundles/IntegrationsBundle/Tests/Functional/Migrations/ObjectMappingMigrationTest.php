<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\IntegrationsBundle\Migrations\Version_0_0_1;
use PHPUnit\Framework\TestCase;

final class ObjectMappingMigrationTest extends TestCase
{
    private Connection $connection;

    private string $prefix;

    private ?string $namespace = null;

    protected function setUp(): void
    {
        EnvLoader::load();
        $driver           = $_ENV['DB_DRIVER'] ?? 'pdo_mysql';
        $this->connection = DriverManager::getConnection([
            'driver' => $driver, 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
            'charset' => 'pdo_pgsql' === $driver ? 'UTF8' : 'utf8mb4',
        ]);
        $this->prefix = 'upgrade_'.bin2hex(random_bytes(6)).'_';
        $this->connection->getConfiguration()->setSchemaAssetsFilter(fn (string|AbstractAsset $asset): bool => str_starts_with($asset instanceof AbstractAsset ? $asset->getName() : $asset, $this->prefix));
        if (DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform())) {
            $this->namespace = $this->prefix.'schema';
            $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
            $this->connection->executeStatement('SET search_path TO '.$this->namespace);
            $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->connection)) {
            return;
        }

        if (null !== $this->namespace) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
        } else {
            $this->connection->executeStatement('DROP TABLE IF EXISTS '.$this->prefix.'sync_object_mapping');
        }
        $this->connection->close();
    }

    public function testUpgradePreservesMappingsAndExistingConstraints(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->connection);
        $migration = new Version_0_0_1($entityManager, $this->prefix);
        $this->assertFalse($migration->shouldExecute());

        $table = new Table($this->prefix.'sync_object_mapping');
        $table->addColumn('id', 'integer');
        foreach (['integration', 'integration_object_name', 'integration_object_id', 'internal_object_name', 'internal_object_id'] as $name) {
            $table->addColumn($name, 'string', ['length' => 191]);
        }
        $table->setPrimaryKey(['id']);
        $table->addIndex(['integration', 'integration_object_name', 'integration_object_id'], $this->prefix.'integration_object');
        $table->addUniqueIndex(['integration', 'internal_object_name', 'internal_object_id'], $this->prefix.'existing_unique');
        $this->connection->createSchemaManager()->createTable($table);
        $mapping = [
            'id' => 42, 'integration' => 'demo', 'integration_object_name' => 'Contact',
            'integration_object_id' => 'external-id', 'internal_object_name' => 'lead', 'internal_object_id' => '123',
        ];
        $this->connection->insert($table->getName(), $mapping);
        $this->assertTrue($migration->shouldExecute());

        $migration->execute();

        $this->assertFalse($migration->shouldExecute());
        $this->assertSame($mapping + ['integration_reference_id' => null], $this->connection->fetchAssociative('SELECT * FROM '.$table->getName()));
        $updated = $this->connection->createSchemaManager()->introspectTable($table->getName());
        $column  = $updated->getColumn('integration_reference_id');
        $this->assertSame(191, $column->getLength());
        $this->assertFalse($column->getNotnull());
        $this->assertSame(['integration', 'integration_object_name', 'integration_object_id', 'integration_reference_id'], $updated->getIndex($this->prefix.'integration_object')->getColumns());
        $this->assertSame(['integration', 'integration_object_name', 'integration_reference_id', 'integration_object_id'], $updated->getIndex($this->prefix.'integration_reference')->getColumns());
        $this->assertInstanceOf(\Doctrine\DBAL\Schema\Index::class, $updated->getPrimaryKey());
        $this->assertSame(['id'], $updated->getPrimaryKey()->getColumns());
        $this->assertTrue($updated->getIndex($this->prefix.'existing_unique')->isUnique());

        $this->connection->update($table->getName(), ['integration_reference_id' => 'reference-123'], ['id' => 42]);
        $this->assertSame('reference-123', $this->connection->fetchOne('SELECT integration_reference_id FROM '.$table->getName()));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->connection->insert($table->getName(), array_replace($mapping, ['id' => 43]));
    }
}
