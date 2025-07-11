<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\EventListener;

use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\EventListener\IntegrationSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IntegrationSubscriberTest extends TestCase
{
    public function testOnRequestLogging(): void
    {
        $event = $this->createMock(PluginIntegrationRequestEvent::class);
        $event->method('getIntegrationName')->willReturn('Integration');
        $event->method('getHeaders')->willReturn(['Authorization: Bearer some_token']);
        $event->method('getMethod')->willReturn('POST');
        $event->method('getUrl')->willReturn('https://mautic.org');
        $event->method('getParameters')->willReturn(['key' => 'value']);
        $event->method('getSettings')->willReturn(['setting' => 'value']);

        $authorization = ['Authorization: Bearer [REDACTED]'];
        $authorization = var_export($authorization, true);

        $logger  = $this->createMock(LoggerInterface::class);
        $matcher = $this->exactly(4);
        $logger->expects($matcher)
            ->method('debug')->willReturnCallback(function (...$parameters) use ($matcher, $authorization) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('INTEGRATION REQUEST URL: POST https://mautic.org', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame("INTEGRATION REQUEST HEADERS: \n".$authorization.PHP_EOL, $parameters[0]);
                }
            });

        $subscriber = new IntegrationSubscriber($logger);
        $subscriber->onRequest($event);
    }
}
