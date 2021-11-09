<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\PluginBundle\EventListener\IntegrationSubscriber;
use Monolog\Logger;

class IntegrationSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    public function testOnResponse()
    {
        $this->logger          = new Logger('application');
        $integrationSubscriber = new IntegrationSubscriber($this->logger);
        $this->assertIsArray($integrationSubscriber->getSubscribedEvents());
        $this->assertNotEmpty($integrationSubscriber->getSubscribedEvents());
    }
}
