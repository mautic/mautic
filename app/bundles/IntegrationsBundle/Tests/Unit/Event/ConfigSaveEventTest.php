<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;

class ConfigSaveEventTest extends TestCase
{
    public function testGetters()
    {
        $form        = $this->createMock(Form::class);
        $name        = 'name';
        $integration = $this->createMock(Integration::class);
        $event       = new ConfigSaveEvent($integration, $form);

        $integration->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        self::assertSame($integration, $event->getIntegrationConfiguration());
        self::assertSame($name, $event->getIntegration());
        self::assertSame($form, $event->getForm());
    }
}
