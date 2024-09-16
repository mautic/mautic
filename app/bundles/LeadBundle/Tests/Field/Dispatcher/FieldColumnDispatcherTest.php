<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher;
use Mautic\LeadBundle\Field\Event\AddColumnEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
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
                'mautic.lead_field_pre_add_column',
                $this->isInstanceOf(AddColumnEvent::class)
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
                'mautic.lead_field_pre_add_column',
                $this->callback(function ($event) {
                    /* @var AddColumnBackgroundEvent $event */
                    return $event instanceof AddColumnEvent;
                })
            );

        $fieldColumnDispatcher = new FieldColumnDispatcher($dispatcher, $backgroundSettings);

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Column change will be processed in background job');

        $fieldColumnDispatcher->dispatchPreAddColumnEvent($leadField);
    }
}
