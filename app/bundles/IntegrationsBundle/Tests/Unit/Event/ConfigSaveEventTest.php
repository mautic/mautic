<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

class ConfigSaveEventTest extends TestCase
{
    public function testGetters()
    {
        $name        = 'name';
        $integration = $this->createMock(Integration::class);
        $event       = new ConfigSaveEvent($integration);

        $integration->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        self::assertSame($integration, $event->getIntegrationConfiguration());
        self::assertSame($name, $event->getIntegration());
    }
}
