<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Dispatcher;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Field\Dispatcher\FieldDeleteDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldDeleteDispatcherTest extends TestCase
{
    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcherMock;

    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManagerMock;

    /**
     * @var MockObject&BackgroundSettings
     */
    private $backgroundSettingsMock;

    private FieldDeleteDispatcher $fieldDeleteDispatcher;

    protected function setUp(): void
    {
        $this->dispatcherMock         = $this->createMock(EventDispatcherInterface::class);
        $this->entityManagerMock      = $this->createMock(EntityManager::class);
        $this->backgroundSettingsMock = $this->createMock(BackgroundSettings::class);
        $this->fieldDeleteDispatcher  = new FieldDeleteDispatcher(
            $this->dispatcherMock,
            $this->entityManagerMock,
            $this->backgroundSettingsMock
        );
    }

    public function testDispatchPreDeleteEventInBackground(): void
    {
        $this->backgroundSettingsMock->expects($this->once())->method('shouldProcessColumnChangeInBackground')->willReturn(true);
        $leadField = new LeadField();

        $this->expectException(AbortColumnUpdateException::class);
        $this->expectExceptionMessage('Column change will be processed in background job');

        $this->fieldDeleteDispatcher->dispatchPreDeleteEvent($leadField);
    }

    public function testDispatchPreDeleteEventNow(): void
    {
        $this->backgroundSettingsMock->expects($this->once())->method('shouldProcessColumnChangeInBackground')->willReturn(false);
        $leadField = new LeadField();
        $this->dispatcherMock->expects($this->once())->method('hasListeners')->willReturn(true);
        $this->dispatcherMock->expects($this->once())->method('dispatch')->with(
            $this->callback(fn ($event) => $event instanceof LeadFieldEvent),
            'mautic.lead_field_pre_delete',
        );
        $this->fieldDeleteDispatcher->dispatchPreDeleteEvent($leadField);
    }
}
