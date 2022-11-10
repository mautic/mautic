<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;

class VersionProviderTest extends \PHPUnit\Framework\TestCase
{
    private $connection;
    private $statement;
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection           = $this->createMock(Connection::class);
        $this->statement            = $this->createMock(Statement::class);
        $this->provider             = new VersionProvider($this->connection);
    }

    public function testGetVersionForMySql()
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('5.7.23-23-log');

        $version = $this->provider->getVersion();

        $this->assertSame('5.7.23-23-log', $version);
        $this->assertFalse($this->provider->isMariaDb());
        $this->assertTrue($this->provider->isMySql());
    }

    public function testGetVersionForMariaDb()
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('10.3.9-MariaDB');

        $version = $this->provider->getVersion();

        $this->assertSame('10.3.9-MariaDB', $version);
        $this->assertTrue($this->provider->isMariaDb());
        $this->assertFalse($this->provider->isMySql());
    }
}
