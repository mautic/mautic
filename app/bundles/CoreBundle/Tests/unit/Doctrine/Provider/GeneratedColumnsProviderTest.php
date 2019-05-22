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

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnsInterface;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GeneratedColumnsProviderTest extends \PHPUnit_Framework_TestCase
{
    private $versionProvider;
    private $dispatcher;
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->versionProvider = $this->createMock(VersionProviderInterface::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->provider        = new GeneratedColumnsProvider($this->versionProvider, $this->dispatcher);

        $this->dispatcher->method('hasListeners')->willReturn(true);
    }

    public function testGetGeneratedColumnsIfNotSupported()
    {
        $notSupportedMySqlVersion = '5.7.13';

        $this->versionProvider->expects($this->once())
            ->method('getVersion')
            ->willReturn($notSupportedMySqlVersion);

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $generatedColumns = $this->provider->getGeneratedColumns();

        $this->assertInstanceOf(GeneratedColumnsInterface::class, $generatedColumns);
        $this->assertCount(0, $generatedColumns);
    }

    public function testGetGeneratedColumnsIfSupported()
    {
        $supportedMySqlVersion = '5.7.14';

        $event = new GeneratedColumnsEvent();
        $event->addGeneratedColumn(new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important'));

        $this->versionProvider->expects($this->exactly(2))
            ->method('getVersion')
            ->willReturn($supportedMySqlVersion);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($event);

        $generatedColumns = $this->provider->getGeneratedColumns();

        $this->assertInstanceOf(GeneratedColumnsInterface::class, $generatedColumns);
        $this->assertGreaterThanOrEqual(1, count($generatedColumns));

        // Ensure that the cache works and dispatcher is called only once
        $generatedColumns = $this->provider->getGeneratedColumns();
    }
}
