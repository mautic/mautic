<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher;
use Mautic\LeadBundle\Field\Event\AddColumnEvent;
use Mautic\LeadBundle\Field\Event\UpdateColumnEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testNoBackground(): void
    {
        $dispatcher         = $this->createMock(EventDispatcherInterface::class);
        $backgroundSettings = $this->createMock(BackgroundSettings::class);
        $leadField          = new LeadField();

        $backgroundSettings->expects($this->once())
            ->method('shouldProcessColumnChangeInBackground')
            ->willReturn(false);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(AddColumnEvent::class),
                'mautic.lead_field_pre_add_column',
            );

        $fieldColumnDispatcher = new FieldColumnDispatcher($dispatcher, $backgroundSettings);

        $fieldColumnDispatcher->dispatchPreAddColumnEvent($leadField);
    }

    public function testStopPropagation(): void
    {
        $leadField          = new LeadField();
        $dispatcher         = $this->createMock(EventDispatcherInterface::class);
        $backgroundSettings = $this->createMock(BackgroundSettings::class);

        $backgroundSettings->expects($this->once())
            ->method('shouldProcessColumnChangeInBackground')
            ->willReturn(true);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(AddColumnEvent::class),
                'mautic.lead_field_pre_add_column'
            );

        $fieldColumnDispatcher = new FieldColumnDispatcher($dispatcher, $backgroundSettings);

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Column change will be processed in background job');

        $fieldColumnDispatcher->dispatchPreAddColumnEvent($leadField);
    }

    public function testStopPropagationUpdate(): void
    {
        $leadField = new LeadField();

        $dispatcher         = $this->createMock(EventDispatcherInterface::class);
        $backgroundSettings = $this->createMock(BackgroundSettings::class);

        $backgroundSettings
            ->expects($this->once())
            ->method('shouldProcessColumnChangeInBackground')
            ->willReturn(true);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(UpdateColumnEvent::class),
                'mautic.lead_field_pre_update_column',
            );

        $fieldColumnDispatcher = new FieldColumnDispatcher($dispatcher, $backgroundSettings);

        $this->expectException(AbortColumnUpdateException::class);
        $this->expectExceptionMessage('Column change will be processed in background job');

        $fieldColumnDispatcher->dispatchPreUpdateColumnEvent($leadField);
    }
}
