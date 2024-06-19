<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use PHPUnit\Framework\MockObject\MockObject;

class VersionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Connection|MockObject
     */
    private MockObject $connection;

    /**
     * @var MockObject&Result
     */
    private MockObject $result;

    private VersionProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection           = $this->createMock(Connection::class);
        $this->result               = $this->createMock(Result::class);
        $this->provider             = new VersionProvider($this->connection);
    }

    public function testGetVersionForMySql(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->result);

        $this->result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('5.7.23-23-log');

        $version = $this->provider->getVersion();

        $this->assertSame('5.7.23-23-log', $version);
        $this->assertFalse($this->provider->isMariaDb());
        $this->assertTrue($this->provider->isMySql());
    }

    public function testGetVersionForMariaDb(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->result);

        $this->result->expects($this->once())
            ->method('fetchOne')
            ->willReturn('10.3.9-MariaDB');

        $version = $this->provider->getVersion();

        $this->assertSame('10.3.9-MariaDB', $version);
        $this->assertTrue($this->provider->isMariaDb());
        $this->assertFalse($this->provider->isMySql());
    }
}
