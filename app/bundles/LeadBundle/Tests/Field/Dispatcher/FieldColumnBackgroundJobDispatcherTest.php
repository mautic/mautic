<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher;
use Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Event\UpdateColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnBackgroundJobDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testNoListener(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);
        $dispatcher
            ->expects($this->never())
            ->method('dispatch');

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $leadField = new LeadField();

        $this->expectException(NoListenerException::class);
        $this->expectExceptionMessage('There is no Listener for this event');

        $fieldColumnBackgroundJobDispatcher->dispatchPreAddColumnEvent($leadField);
    }

    public function testNoListenerUpdate(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);
        $dispatcher
            ->expects($this->never())
            ->method('dispatch');

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $leadField = new LeadField();

        $this->expectException(NoListenerException::class);
        $this->expectExceptionMessage('There is no Listener for this event');

        $fieldColumnBackgroundJobDispatcher->dispatchPreUpdateColumnEvent($leadField);
    }

    public function testNormalProcess(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(AddColumnBackgroundEvent::class),
                'mautic.lead_field_pre_add_column_background_job',
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $leadField = new LeadField();

        $fieldColumnBackgroundJobDispatcher->dispatchPreAddColumnEvent($leadField);
    }

    public function testNormalProcessUpdate(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(UpdateColumnBackgroundEvent::class),
                'mautic.lead_field_pre_update_column_background_job',
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $leadField = new LeadField();

        $fieldColumnBackgroundJobDispatcher->dispatchPreUpdateColumnEvent($leadField);
    }

    public function testStopPropagation(): void
    {
        $leadField = new LeadField();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($event) {
                    /* @var AddColumnBackgroundEvent $event */
                    $event->stopPropagation();

                    return $event instanceof AddColumnBackgroundEvent;
                }),
                'mautic.lead_field_pre_add_column_background_job'
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Column cannot be created now');

        $fieldColumnBackgroundJobDispatcher->dispatchPreAddColumnEvent($leadField);
    }

    public function testStopPropagationUpdate(): void
    {
        $leadField = new LeadField();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($event) {
                    /* @var AddColumnBackgroundEvent $event */
                    $event->stopPropagation();

                    return $event instanceof UpdateColumnBackgroundEvent;
                }),
                'mautic.lead_field_pre_update_column_background_job',
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $this->expectException(AbortColumnUpdateException::class);
        $this->expectExceptionMessage('Column cannot be updated now');

        $fieldColumnBackgroundJobDispatcher->dispatchPreUpdateColumnEvent($leadField);
    }
}
