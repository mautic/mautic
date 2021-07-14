<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher;
use Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
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
                'mautic.lead_field_pre_add_column_background_job',
                $this->isInstanceOf(AddColumnBackgroundEvent::class)
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $leadField = new LeadField();

        $fieldColumnBackgroundJobDispatcher->dispatchPreAddColumnEvent($leadField);
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
                'mautic.lead_field_pre_add_column_background_job',
                $this->callback(function ($event) {
                    /* @var AddColumnBackgroundEvent $event */
                    $event->stopPropagation();

                    return $event instanceof AddColumnBackgroundEvent;
                })
            );

        $fieldColumnBackgroundJobDispatcher = new FieldColumnBackgroundJobDispatcher($dispatcher);

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Column cannot be created now');

        $fieldColumnBackgroundJobDispatcher->dispatchPreAddColumnEvent($leadField);
    }
}
