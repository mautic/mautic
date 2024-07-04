<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    /**
     * @var ConfigChangeLogger|MockObject
     */
    private MockObject $logger;

    private ConfigSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->logger     = $this->createMock(ConfigChangeLogger::class);
        $ipAddressRepo    = $this->createMock(IpAddressRepository::class);
        $coreParamHelper  = $this->createMock(CoreParametersHelper::class);
        $auditLogRepo     = $this->createMock(AuditLogRepository::class);
        $this->subscriber = new ConfigSubscriber($this->logger, $ipAddressRepo, $coreParamHelper, $auditLogRepo);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                ConfigEvents::CONFIG_POST_SAVE => ['onConfigPostSave', 0],
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testNothingToLogOnConfigPostSave(): void
    {
        // Test nothing to log
        $this->logger->expects($this->never())
            ->method('log');
        $event = $this->createMock(ConfigEvent::class);
        $event->expects($this->once())
            ->method('getOriginalNormData')
            ->willReturn(null);

        $this->subscriber->onConfigPostSave($event);
    }

    public function testSomethingToLogOnConfigPostSave(): void
    {
        // Test something to log
        $originalNormData = ['orig'];
        $normData         = ['norm'];

        $event = $this->createMock(ConfigEvent::class);
        $event->expects($this->once())
            ->method('getOriginalNormData')
            ->willReturn($originalNormData);
        $event->expects($this->once())
            ->method('getNormData')
            ->willReturn($normData);
        $this->logger->expects($this->once())
            ->method('setOriginalNormData')
            ->with($originalNormData)
            ->willReturn($this->logger);
        $this->logger->expects($this->once())
            ->method('log')
            ->with($normData);

        $this->subscriber->onConfigPostSave($event);
    }
}
