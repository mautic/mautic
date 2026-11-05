<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\InstallBundle\Helper\SchemaHelper;
use PHPUnit\Framework\TestCase;

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

    private string $parentTableName;

    private string $childTableName;

    /**
     * @var AbstractSchemaManager<T>
     */
    private AbstractSchemaManager $schemaManager;

    protected function setUp(): void
    {
        parent::setUp();
        EnvLoader::load();

        $this->dbParams = [
            'driver'        => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
            'host'          => $_ENV['DB_HOST'],
            'port'          => $_ENV['DB_PORT'],
            'dbname'        => $_ENV['DB_NAME'], // Doctrine needs 'dbname', not 'name'
            'user'          => $_ENV['DB_USER'],
            'password'      => $_ENV['DB_PASSWD'],
            'table_prefix'  => MAUTIC_TABLE_PREFIX,
            'backup_prefix' => 'bak_',
        ];

        $this->connection = DriverManager::getConnection($this->dbParams);

        $this->indexTableName = 'table_with_index';

        $t = new Table($this->indexTableName);
        $t->addColumn('a_column', 'text');

        // Create an index that has options, e.g. length of the index
        $indexOptions = [
            'lengths' => [
                0 => 128,
            ],
        ];
        $t->addIndex(['a_column'], 'index_with_options', [], $indexOptions);
        $this->schemaManager = $this->connection->createSchemaManager();
        $this->schemaManager->createTable($t);

        $this->parentTableName = 'table_with_fk_parent';
        $this->childTableName  = 'table_with_fk_child';

        $parent = new Table($this->parentTableName);
        $parent->addColumn('id', 'integer', ['unsigned' => true]);
        $parent->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $this->schemaManager->createTable($parent);

        $child = new Table($this->childTableName);
        $child->addColumn('parent_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $child->addIndex(['parent_id'], 'parent_id_search');
        // the referential action has to survive being copied onto the backup table
        $child->addForeignKeyConstraint($this->parentTableName, ['parent_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_to_parent');
        $this->schemaManager->createTable($child);
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

        foreach ([$this->childTableName, $this->parentTableName] as $table) {
            foreach (['', $this->dbParams['backup_prefix']] as $prefix) {
                if ($this->schemaManager->tablesExist([$prefix.$table])) {
                    $this->schemaManager->dropTable($prefix.$table);
                }
            }
        }
    }

    public function testBackupIndexesWithConfigOptions(): void
    {
        $schemaHelper = new SchemaHelper($this->dbParams, $this->createStub(EntityManagerInterface::class));

        // Make the backupExistingSchema method public so we can test that functionality without mocking all the SchemaHelper's functionality.
        $controllerReflection = new \ReflectionClass(SchemaHelper::class);
        $method               = $controllerReflection->getMethod('backupExistingSchema');

        // Set the platform property, as that one is only set in the installSchema method, which we want to avoid.
        $property   = $controllerReflection->getProperty('platform');
        $connection = DriverManager::getConnection($this->dbParams);
        $property->setValue($schemaHelper, $connection->getDatabasePlatform());

        $tables       = [$this->indexTableName];
        $mauticTables = [$this->indexTableName => $this->dbParams['backup_prefix'].$this->indexTableName];

        $sql = $method->invokeArgs($schemaHelper, [$tables, $mauticTables, $this->dbParams['backup_prefix']]);

        $exceptions = [];
        if (!empty($sql)) {
            foreach ($sql as $q) {
                try {
                    $this->connection->executeStatement($q);
                } catch (\Exception $exception) {
                    $exceptions[] = $exception->getMessage();
                }
            }
        }
        $this->connection->close();

        $this->assertSame([], $exceptions);
    }

    public function testBackupForeignKeysKeepTheirReferentialActions(): void
    {
        $schemaHelper = new SchemaHelper($this->dbParams, $this->createStub(EntityManagerInterface::class));

        $controllerReflection = new \ReflectionClass(SchemaHelper::class);
        $method               = $controllerReflection->getMethod('backupExistingSchema');

        $property   = $controllerReflection->getProperty('platform');
        $connection = DriverManager::getConnection($this->dbParams);
        $property->setValue($schemaHelper, $connection->getDatabasePlatform());

        $tables       = [$this->childTableName, $this->parentTableName];
        $mauticTables = [
            $this->childTableName  => $this->dbParams['backup_prefix'].$this->childTableName,
            $this->parentTableName => $this->dbParams['backup_prefix'].$this->parentTableName,
        ];

        /** @var list<string> $sql */
        $sql = $method->invokeArgs($schemaHelper, [$tables, $mauticTables, $this->dbParams['backup_prefix']]);

        $addForeignKey = array_values(array_filter($sql, static fn (string $query): bool => str_contains($query, 'ADD CONSTRAINT')));
        $this->assertCount(1, $addForeignKey, 'The backup table should get the foreign key back.');
        $this->assertStringContainsString('ON DELETE CASCADE', $addForeignKey[0], 'The referential action has to be carried over.');

        $exceptions = [];
        foreach ($sql as $q) {
            try {
                $this->connection->executeStatement($q);
            } catch (\Exception $exception) {
                $exceptions[] = $exception->getMessage();
            }
        }
        $this->connection->close();

        $this->assertSame([], $exceptions);
    }
}
