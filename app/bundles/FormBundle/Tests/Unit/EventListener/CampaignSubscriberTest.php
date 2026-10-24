<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Unit\EventListener;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\EventListener\CampaignSubscriber;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\TestCase;

final class CampaignSubscriberTest extends TestCase
{
    public function testOnFormSubmitDoesNotResolveCampaignExecutionWithoutLead(): void
    {
        $formModel            = $this->createStub(FormModel::class);
        $realTimeExecutioner  = $this->createMock(RealTimeExecutioner::class);
        $formFieldHelper      = $this->createStub(FormFieldHelper::class);
        $formRepository       = $this->createStub(FormRepository::class);
        $submissionRepository = $this->createStub(SubmissionRepository::class);

        $event = $this->createMock(SubmissionEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn(null);

        $event->expects($this->never())
            ->method('getSubmission');

        $realTimeExecutioner->expects($this->never())
            ->method('execute');

        $subscriber = new CampaignSubscriber(
            $formModel,
            $realTimeExecutioner,
            $formFieldHelper,
            $formRepository,
            $submissionRepository
        );

        $subscriber->onFormSubmit($event);
    }
}
