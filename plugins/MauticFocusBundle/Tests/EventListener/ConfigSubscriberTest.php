<?php

declare(strict_types = 1);
/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use MauticPlugin\MauticFocusBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    /**
     * @var ConfigEvent|MockObject
     */
    private $configEvent;

    /**
     * @var ConfigSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        $this->configEvent          = $this->createMock(ConfigEvent::class);
        $this->subscriber           = new ConfigSubscriber();
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerateTracking', 0],
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }
}
