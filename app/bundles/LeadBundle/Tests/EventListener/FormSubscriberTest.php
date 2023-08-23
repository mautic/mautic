<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\EventListener\FormSubscriber;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Model\PointGroupModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class FormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoNotContact|(DoNotContact&MockObject)|MockObject
     */
    private DoNotContact|MockObject $doNotContact;

    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadModel;

    /**
     * @var PointGroupModel|(PointGroupModel&object&MockObject)|(PointGroupModel&MockObject)|(object&MockObject)|MockObject
     */
    private MockObject|PointGroupModel $pointGroupModel;

    /**
     * @var FormSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|ContactTracker
     */
    private $contactTracker;

    /**
     * @var MockObject|LeadFieldRepository
     */
    private $leadFieldRepostory;

    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    protected function setUp(): void
    {
        $this->leadModel          = $this->createMock(LeadModel::class);
        $this->contactTracker     = $this->createMock(ContactTracker::class);
        $this->ipLookupHelper     = $this->createMock(IpLookupHelper::class);
        $this->leadFieldRepostory = $this->createMock(LeadFieldRepository::class);
        $this->pointGroupModel    = $this->createMock(PointGroupModel::class);
        $this->doNotContact       = $this->createMock(DoNotContact::class);
        $this->subscriber         = new FormSubscriber(
            $this->leadModel,
            $this->contactTracker,
            $this->ipLookupHelper,
            $this->leadFieldRepostory,
            $this->pointGroupModel,
            $this->doNotContact
        );
    }

    public function testOnFormSubmitActionChangePoints(): void
    {
        $this->contactTracker->method('getContact')->willReturn(new Lead());

        $this->ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $submission = new Submission();
        $submission->setForm(new Form());
        $submission->setLead(new Lead());

        $submissionEvent = new SubmissionEvent($submission, [], [], new Request());

        $action = new Action();
        $action->setType('lead.pointschange');
        $action->setProperties(['points' => 1, 'operator' => 'plus']);
        $submissionEvent->setAction($action);

        $this->subscriber->onFormSubmitActionChangePoints($submissionEvent);

        $this->assertEquals(1, $submissionEvent->getSubmission()->getLead()->getPoints());
    }
}
