<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\EventListener\FormSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Model\PointGroupModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

final class FormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&LeadModel
     */
    private MockObject $leadModel;

    private FormSubscriber $subscriber;

    /**
     * @var MockObject&ContactTracker
     */
    private MockObject $contactTracker;

    /**
     * @var MockObject&SubmissionEvent
     */
    private MockObject $submissionEvent;

    protected function setUp(): void
    {
        $this->leadModel          = $this->createMock(LeadModel::class);
        $this->contactTracker     = $this->createMock(ContactTracker::class);
        $this->submissionEvent    = $this->createMock(SubmissionEvent::class);
        $this->subscriber         = new FormSubscriber(
            $this->leadModel,
            $this->contactTracker,
            $this->createStub(LeadFieldRepository::class),
            $this->createStub(PointGroupModel::class),
            $this->createStub(DoNotContact::class),
            $this->createStub(FieldModel::class)
        );
    }

    public function testOnFormSubmitActionChangePoints(): void
    {
        $this->contactTracker->method('getContact')->willReturn(new Lead());

        $ipAddress  = new IpAddress('203.0.113.8');
        $submission = new Submission();
        $submission->setForm(new Form());
        $submission->setLead(new Lead());
        $submission->setIpAddress($ipAddress);

        $submissionEvent = new SubmissionEvent($submission, [], [], new Request());

        $action = new Action();
        $action->setType('lead.pointschange');
        $action->setProperties(['points' => 1, 'operator' => 'plus']);
        $submissionEvent->setAction($action);

        $this->subscriber->onFormSubmitActionChangePoints($submissionEvent);

        $this->assertEquals(1, $submissionEvent->getSubmission()->getLead()->getPoints());
        $this->assertSame($ipAddress, $this->contactTracker->getContact()->getPointsChangeLog()->first()->getIpAddress());
    }

    /**
     * A kiosk mode submission without contact fields has no contact, so the actions must leave the tracked contact alone.
     */
    public function testContactActionsAreSkippedWhenTheSubmissionHasNoContact(): void
    {
        $this->contactTracker->expects($this->never())->method('getContact');
        $this->leadModel->expects($this->never())->method($this->anything());

        $submission = new Submission();
        $submission->setForm(new Form());

        $actions = [
            'lead.pointschange'           => ['points' => 1, 'operator' => 'plus'],
            'lead.changelist'             => ['addToLists' => [1], 'removeFromLists' => []],
            'lead.changetags'             => ['add_tags' => ['kiosk'], 'remove_tags' => []],
            'lead.addutmtags'             => [],
            'lead.scorecontactscompanies' => ['score' => 1],
            'lead.updatelead'             => ['lastname' => 'Updated'],
        ];

        foreach ($actions as $type => $properties) {
            $action = new Action();
            $action->setType($type);
            $action->setProperties($properties);

            $submissionEvent = new SubmissionEvent($submission, [], [], new Request());
            $submissionEvent->setAction($action);

            $this->subscriber->onFormSubmitActionChangePoints($submissionEvent);
            $this->subscriber->onFormSubmitActionChangeList($submissionEvent);
            $this->subscriber->onFormSubmitActionChangeTags($submissionEvent);
            $this->subscriber->onFormSubmitActionAddUtmTags($submissionEvent);
            $this->subscriber->onFormSubmitActionScoreContactsCompanies($submissionEvent);
            $this->subscriber->onFormSubmitActionUpdateLead($submissionEvent);
        }
    }

    public function testThatTheLeadIsAddedToTheSegmentOnLeadOnSegmentsChangeEvent(): void
    {
        $this->submissionEvent
            ->method('getActionConfig')
            ->willReturn([
                'addToLists'      => 1,
                'removeFromLists' => null,
            ]);

        $this->leadModel->expects($this->once())->method('addToLists');
        $this->subscriber->onLeadSegmentsChange($this->submissionEvent);
    }

    public function testThatTheLeadIsRemovedFromTheSegmentOnLeadOnSegmentsChangeEvent(): void
    {
        $this->submissionEvent
            ->method('getActionConfig')
            ->willReturn([
                'removeFromLists' => 1,
                'addToLists'      => null,
            ]);

        $this->leadModel->expects($this->once())->method('removeFromLists');
        $this->subscriber->onLeadSegmentsChange($this->submissionEvent);
    }

    public function testThatTheObserverForTriggerOnLeadSegmentsChangeEventIsFired(): void
    {
        $subscribers = FormSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(LeadEvents::LEAD_ON_SEGMENTS_CHANGE, $subscribers);
        $this->assertSame(['onLeadSegmentsChange', 0], $subscribers[LeadEvents::LEAD_ON_SEGMENTS_CHANGE]);
    }
}
