<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Connection;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper;
use Mautic\CoreBundle\Doctrine\Connection\PrimaryReadReplicaConnectionWrapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConnectionWrapperTest extends TestCase
{
    #[DataProvider('provideWrappers')]
    public function testInstallerSwitchesDriverAndCachedPlatform(string $wrapper): void
    {
        $configuration = new Configuration();
        $middleware    = new class() implements Middleware {
            public int $wraps = 0;

            public function wrap(Driver $driver): Driver
            {
                ++$this->wraps;

                return $driver;
            }
        };
        $configuration->setMiddlewares([$middleware]);
        $events = new EventManager();
        $params = [
            'driver'        => 'pdo_mysql',
            'wrapperClass'  => $wrapper,
            'serverVersion' => '8.4.0',
            'charset'       => 'utf8mb4',
            'driverOptions' => [\PDO::ATTR_STRINGIFY_FETCHES => true, 1000 => false],
        ];
        if (PrimaryReadReplicaConnectionWrapper::class === $wrapper) {
            $params['primary'] = ['host' => 'old-primary', 'dbname' => 'old_db', 'charset' => 'utf8mb4'];
            $params['replica'] = ['reader' => ['host' => 'reader', 'dbname' => 'old_db', 'charset' => 'utf8mb4']];
            $params['keepReplica'] = true;
        }
        /** @var ConnectionWrapper|PrimaryReadReplicaConnectionWrapper $connection */
        $connection = DriverManager::getConnection($params, $configuration, $events);
        $this->assertInstanceOf(AbstractMySQLPlatform::class, $connection->getDatabasePlatform());

        $connection->initConnection([
            'driver' => 'pdo_pgsql', 'host' => 'postgres', 'port' => null,
            'name' => 'install_db', 'user' => 'installer', 'password' => 'test', 'charset' => 'UTF8',
        ]);

        $this->assertInstanceOf(PostgreSQLPlatform::class, $connection->getDatabasePlatform());
        $this->assertFalse($connection->isConnected());
        $this->assertSame($configuration, $connection->getConfiguration());
        // @phpstan-ignore method.deprecated, method.deprecated (Verify DBAL 3 preserves the EventManager on both wrapper types.)
        $this->assertSame($events, $connection->getEventManager());
        $this->assertSame(2, $middleware->wraps);
        $params = $connection->getParams();
        $this->assertSame('pdo_pgsql', $params['driver']);
        $this->assertSame('UTF8', $params['charset']);
        $this->assertSame([\PDO::ATTR_STRINGIFY_FETCHES => true], $params['driverOptions']);
        $this->assertArrayNotHasKey('port', $params);
        $this->assertArrayNotHasKey('serverVersion', $params);
        if (PrimaryReadReplicaConnectionWrapper::class === $wrapper) {
            $this->assertSame('postgres', $params['primary']['host']);
            $this->assertSame('install_db', $params['primary']['dbname']);
            $this->assertSame('reader', $params['replica']['reader']['host']);
            $this->assertSame('install_db', $params['replica']['reader']['dbname']);
            $this->assertSame('UTF8', $params['replica']['reader']['charset']);
            $this->assertTrue($params['keepReplica']);
        }
    }

    public static function provideWrappers(): iterable
    {
        yield [ConnectionWrapper::class];
        yield [PrimaryReadReplicaConnectionWrapper::class];
    }

    #[DataProvider('provideWrappers')]
    public function testMysqlSettingsRemainAvailableWhenTheDriverDoesNotChange(string $wrapper): void
    {
        $params = ['driver' => 'pdo_mysql', 'wrapperClass' => $wrapper, 'serverVersion' => '8.4.0', 'driverOptions' => [1000 => false]];
        if (PrimaryReadReplicaConnectionWrapper::class === $wrapper) {
            $params['primary'] = ['host' => 'old-primary'];
            $params['replica'] = [['host' => 'reader']];
        }
        /** @var ConnectionWrapper|PrimaryReadReplicaConnectionWrapper $connection */
        $connection = DriverManager::getConnection($params);
        $connection->initConnection([
            'driver' => 'pdo_mysql', 'host' => 'mysql', 'port' => 3307,
            'name' => 'install_db', 'user' => 'installer', 'password' => 'test', 'charset' => 'latin1',
        ]);

        $params = $connection->getParams();
        $this->assertInstanceOf(AbstractMySQLPlatform::class, $connection->getDatabasePlatform());
        $this->assertSame([1000 => false], $params['driverOptions']);
        $this->assertSame(3307, $params['port']);
        $this->assertSame('latin1', $params['charset']);
    }
}
