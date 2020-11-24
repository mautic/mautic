<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class VersionProviderTest extends \PHPUnit\Framework\TestCase
{
    private $connection;
    private $coreParametersHelper;
    private $statement;
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection           = $this->createMock(Connection::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->statement            = $this->createMock(Statement::class);
        $this->provider             = new VersionProvider($this->connection, $this->coreParametersHelper);
    }

    public function testGetVersionFromConfig()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->willReturn('5.7.23-0ubuntu0.18.04.1');

        $version = $this->provider->getVersion();

        $this->assertSame('5.7.23-0ubuntu0.18.04.1', $version);
        $this->assertFalse($this->provider->isMariaDb());
        $this->assertTrue($this->provider->isMySql());
    }

    public function testGetVersionForMySql()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->willReturn(null);

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
        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->willReturn('5.7');

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
