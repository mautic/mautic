<?php

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Mautic\InstallBundle\Helper\SchemaHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\Dotenv\Dotenv;

class InstallSchemaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array<string, mixed>
     */
    protected $dbParams;

    /**
     * @var string
     */
    protected $indexTableName;

    public function setUp(): void
    {
        parent::setUp();

        // Load environment variables with same logic as the config_test.php
        $env     = new Dotenv();
        $root    = __DIR__.'/../../../../../';
        $envFile = file_exists($root.'.env') ? $root.'.env' : $root.'.env.dist';

        $env->load($envFile);
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->dbParams = [
            'driver'        => getenv('DB_DRIVER') ?: 'pdo_mysql',
            'host'          => getenv('DB_HOST'),
            'port'          => getenv('DB_PORT'),
            'dbname'        => getenv('DB_NAME'), // Doctrine needs 'dbname', not 'name'
            'user'          => getenv('DB_USER'),
            'password'      => getenv('DB_PASSWD'),
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
        $this->connection->getSchemaManager()->createTable($t);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->connection->getSchemaManager()->tablesExist([$this->indexTableName])) {
            $this->connection->getSchemaManager()->dropTable($this->indexTableName);
        }
        if ($this->connection->getSchemaManager()->tablesExist([$this->dbParams['backup_prefix'].$this->indexTableName])) {
            $this->connection->getSchemaManager()->dropTable($this->dbParams['backup_prefix'].$this->indexTableName);
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
        $property->setValue($schemaHelper, DriverManager::getConnection($this->dbParams)->getSchemaManager()->getDatabasePlatform());

        $tables       = [$this->indexTableName];
        $mauticTables = [$this->indexTableName => $this->dbParams['backup_prefix'].$this->indexTableName];

        $sql = $method->invokeArgs($schemaHelper, [$tables, $mauticTables, $this->dbParams['backup_prefix']]);

        $exceptions = [];
        if (!empty($sql)) {
            foreach ($sql as $q) {
                try {
                    $this->connection->query($q);
                } catch (\Exception $exception) {
                    $exceptions[] = $exception->getMessage();
                }
            }
        }
        $this->connection->close();

        Assert::assertSame([], $exceptions);
    }
}
