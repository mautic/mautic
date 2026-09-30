<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Unit\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\EventListener\PointSubscriber;
use Mautic\PointBundle\Model\PointModel;
use PHPUnit\Framework\TestCase;

final class PointSubscriberTest extends TestCase
{
    public function testOnFormSubmitDoesNotTriggerPointActionsWithoutLead(): void
    {
        $pointModel = $this->createMock(PointModel::class);

        $event = $this->createMock(SubmissionEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn(null);

        $event->expects($this->never())
            ->method('getSubmission');

        $pointModel->expects($this->never())
            ->method('triggerAction');

        $subscriber = new PointSubscriber($pointModel);

        $subscriber->onFormSubmit($event);
    }
}
