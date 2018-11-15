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

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnsInterface;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GeneratedColumnsProviderTest extends \PHPUnit_Framework_TestCase
{
    private $versionProvider;
    private $dispatcher;
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->versionProvider = $this->createMock(VersionProviderInterface::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->provider        = new GeneratedColumnsProvider($this->versionProvider, $this->dispatcher);
    }

    public function testGetGeneratedColumnsIfNotSupported()
    {
        $notSupportedMySqlVersion = '5.6.0';

        $this->versionProvider->expects($this->once())
            ->method('fetchVersion')
            ->willReturn($notSupportedMySqlVersion);

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $generatedColumns = $this->provider->getGeneratedColumns();

        $this->assertInstanceOf(GeneratedColumnsInterface::class, $generatedColumns);
        $this->assertCount(0, $generatedColumns);
    }
}
