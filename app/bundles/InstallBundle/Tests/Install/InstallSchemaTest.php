<?php

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\InstallBundle\Helper\SchemaHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @template T of AbstractPlatform
 */
class InstallSchemaTest extends TestCase
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

    public function setUp(): void
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
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->schemaManager->tablesExist([$this->indexTableName])) {
            $this->schemaManager->dropTable($this->indexTableName);
        }
        if ($this->schemaManager->tablesExist([$this->dbParams['backup_prefix'].$this->indexTableName])) {
            $this->schemaManager->dropTable($this->dbParams['backup_prefix'].$this->indexTableName);
        }
    }

    public function testBackupIndexesWithConfigOptions(): void
    {
        $schemaHelper = new SchemaHelper($this->dbParams);

        // Make the backupExistingSchema method public so we can test that functionality without mocking all the SchemaHelper's functionality.
        $controllerReflection = new \ReflectionClass(SchemaHelper::class);
        $method               = $controllerReflection->getMethod('backupExistingSchema');
        $method->setAccessible(true);

        // Set the platform property, as that one is only set in the installSchema method, which we want to avoid.
        $property = $controllerReflection->getProperty('platform');
        $property->setAccessible(true);
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

        Assert::assertSame([], $exceptions);
    }
}
