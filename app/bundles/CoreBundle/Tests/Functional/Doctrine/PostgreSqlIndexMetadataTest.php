<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\PageBundle\Entity\Hit;
use PHPUnit\Framework\TestCase;

final class PostgreSqlIndexMetadataTest extends TestCase
{
    private Connection $connection;

    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL does not support MySQL index prefix lengths.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql', 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'index_metadata_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    public function testPageUrlIndexRoundTripsWithoutRecreatingIt(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->connection);
        $metadata = new ClassMetadata(Hit::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        Hit::loadMetadata($metadata);
        (new DoctrineEventsSubscriber(MAUTIC_TABLE_PREFIX))->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $entityManager));
        $definition = $metadata->table['indexes'][MAUTIC_TABLE_PREFIX.'page_hit_url'];
        $table      = new Table($metadata->getTableName());
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addColumn('url', 'text');
        $table->addIndex($definition['columns'], MAUTIC_TABLE_PREFIX.'page_hit_url', [], $definition['options']);
        $manager = $this->connection->createSchemaManager();
        $manager->createTable($table);
        $this->connection->insert($table->getName(), ['id' => 1, 'url' => 'https://example.com/path']);

        $actual = $manager->introspectTable($table->getName());
        $diff   = $manager->createComparator()->compareTables($actual, $table);

        $this->assertTrue($actual->hasIndex(MAUTIC_TABLE_PREFIX.'page_hit_url'));
        $this->assertSame([], $this->connection->getDatabasePlatform()->getAlterTableSQL($diff));
        $this->assertSame('https://example.com/path', $this->connection->fetchOne('SELECT url FROM '.$table->getName()));
    }

    public function testForeignKeyIntrospectionOnlyIncludesPhysicalIndexes(): void
    {
        $schema = new Schema();
        $parent = $schema->createTable(MAUTIC_TABLE_PREFIX.'fk_parent');
        $parent->addColumn('id', 'integer');
        $parent->setPrimaryKey(['id']);
        $child = $schema->createTable(MAUTIC_TABLE_PREFIX.'fk_child');
        foreach (['parent_id', 'other_id', 'other_parent_id'] as $columnName) {
            $child->addColumn($columnName, 'integer');
        }
        $child->setPrimaryKey(['parent_id', 'other_id']);
        $child->addIndex(['other_parent_id'], MAUTIC_TABLE_PREFIX.'real_fk_index');
        $child->addForeignKeyConstraint($parent, ['parent_id'], ['id']);
        $child->addForeignKeyConstraint($parent, ['other_parent_id'], ['id']);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->connection);
        (new DoctrineEventsSubscriber(MAUTIC_TABLE_PREFIX))->postGenerateSchema(new GenerateSchemaEventArgs($entityManager, $schema));
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
        $this->connection->insert($parent->getName(), ['id' => 1]);
        $this->connection->insert($child->getName(), ['parent_id' => 1, 'other_id' => 2, 'other_parent_id' => 1]);
        $this->connection->getConfiguration()->setSchemaAssetsFilter(static fn (string|AbstractAsset $asset): bool => in_array($asset instanceof AbstractAsset ? $asset->getName() : $asset, [$parent->getName(), $child->getName()], true));
        $manager = $this->connection->createSchemaManager();

        foreach ([$manager->introspectTable($child->getName()), $manager->introspectSchema()->getTable($child->getName())] as $actual) {
            $this->assertCount(2, $actual->getIndexes());
            $this->assertTrue($actual->hasIndex(MAUTIC_TABLE_PREFIX.'real_fk_index'));
            $this->assertCount(2, $actual->getForeignKeys());
            $diff = $manager->createComparator()->compareTables($actual, $child);
            $this->assertSame([], $this->connection->getDatabasePlatform()->getAlterTableSQL($diff));
        }
        $this->assertSame(1, $this->connection->fetchOne('SELECT COUNT(*) FROM '.$child->getName()));
    }
}
