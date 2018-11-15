<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Doctrine\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;

class VersionProviderTest extends \PHPUnit_Framework_TestCase
{
    private $connection;
    private $statement;
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->provider   = new VersionProvider($this->connection);
    }

    public function testFetchVersionForMySql()
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('5.7.23-23-log');

        $version = $this->provider->fetchVersion();

        $this->assertSame('5.7.23-23-log', $version);
        $this->assertFalse($this->provider->isMariaDb());
        $this->assertTrue($this->provider->isMySql());
    }

    public function testFetchVersionForMariaDb()
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT VERSION()')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('10.3.9-MariaDB');

        $version = $this->provider->fetchVersion();

        $this->assertSame('10.3.9-MariaDB', $version);
        $this->assertTrue($this->provider->isMariaDb());
        $this->assertFalse($this->provider->isMySql());
    }
}
