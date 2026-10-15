<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Connection;

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
    public function testFreshMysqlConnectionSwitchesToPostgreSql(string $wrapper): void
    {
        if ('pdo_pgsql' !== getenv('DB_DRIVER')) {
            $this->markTestSkipped('Requires a PostgreSQL database for the installer driver transition.');
        }
        $host   = getenv('DB_HOST');
        $params = ['driver' => 'pdo_mysql', 'serverVersion' => '8.4.0', 'wrapperClass' => $wrapper];
        if (PrimaryReadReplicaConnectionWrapper::class === $wrapper) {
            $params['primary'] = ['host' => 'unconfigured'];
            $params['replica'] = [['host' => $host]];
        }
        /** @var ConnectionWrapper|PrimaryReadReplicaConnectionWrapper $connection */
        $connection = DriverManager::getConnection($params);
        $this->assertInstanceOf(AbstractMySQLPlatform::class, $connection->getDatabasePlatform());
        $connection->initConnection([
            'driver' => 'pdo_pgsql', 'host' => $host, 'port' => getenv('DB_PORT'),
            'name' => getenv('DB_NAME'), 'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWD'), 'charset' => 'UTF8',
        ]);
        try {
            $this->assertInstanceOf(PostgreSQLPlatform::class, $connection->getDatabasePlatform());
            $this->assertSame(getenv('DB_NAME'), $connection->fetchOne('SELECT current_database()'));
            $this->assertSame('UTF8', $connection->fetchOne("SELECT current_setting('client_encoding')"));
        } finally {
            $connection->close();
        }
    }

    public static function provideWrappers(): iterable
    {
        yield [ConnectionWrapper::class];
        yield [PrimaryReadReplicaConnectionWrapper::class];
    }
}
