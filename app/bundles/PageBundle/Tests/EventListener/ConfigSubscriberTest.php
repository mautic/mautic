<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\PageBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    public function testOnConfigSaveWithFooterScript(): void
    {
        $values = [
            'pageconfig' => [
                'footer_script' => '<script>test</script>',
            ],
        ];

        $configEvent = $this->createMock(ConfigEvent::class);
        $configEvent->expects($this->once())->method('getConfig')->willReturn($values);
        $configEvent->expects($this->once())->method('setConfig');

        $configSubscriber = new ConfigSubscriber();
        $configSubscriber->onConfigSave($configEvent);
    }
}
