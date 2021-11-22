<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\FormSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\HttpFoundation\Request;

class FormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    const REFERER_WITH_UTM = 'https://domain.tld?utm_campaign=test&utm_source=test';

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    /**
     * @var EmailModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $emailModel;

    /**
     * @var IpLookupHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ipLookupHelper;

    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadModel;

    private Submission $submission;

    protected function setUp(): void
    {
        $this->emailModel     = $this->createMock(EmailModel::class);
        $this->leadModel      = $this->createMock(LeadModel::class);
        $this->contactTracker = $this->createMock(ContactTracker::class);
        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);

        $submission           = new Submission();
        $submission->setForm(new Form());
        $submission->setLead(new Lead());

        $this->submission = $submission;
    }

    public function testOnFormSubmitActionAddUtmTagsNever(): void
    {
        $request = new Request();

        $this->leadModel->expects($this->never())->method('getUtmTagRepository');
        $this->leadModel->expects($this->never())->method('setUtmTags');

        $this->triggeFormSubmitActionAddUtmTags($request);
    }

    public function testOnFormSubmitActionAddUtmTagsReferer(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => self::REFERER_WITH_UTM]);

        $this->leadModel->expects($this->once())->method('getUtmTagRepository')->willReturn(new class() {
            public function saveEntity(): void
            {
            }
        });
        $this->leadModel->expects($this->once())->method('setUtmTags');

        $this->triggeFormSubmitActionAddUtmTags($request);
    }

    public function testOnFormSubmitActionAddUtmTagsRequest(): void
    {
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'utm_campaign=test&utm_source=test']);

        $this->leadModel->expects($this->once())->method('getUtmTagRepository')->willReturn(new class() {
            public function saveEntity(): void
            {
            }
        });
        $this->leadModel->expects($this->once())->method('setUtmTags');

        $this->triggeFormSubmitActionAddUtmTags($request);
    }

    public function testOnFormSubmitActionAddUtmTagsSubmission(): void
    {
        $request = new Request();

        $this->leadModel->expects($this->once())->method('getUtmTagRepository')->willReturn(new class() {
            public function saveEntity(): void
            {
            }
        });
        $this->leadModel->expects($this->once())->method('setUtmTags');

        $this->submission->setReferer(self::REFERER_WITH_UTM);

        $this->triggeFormSubmitActionAddUtmTags($request);
    }

    public function testOnFormSubmitActionChangePoints()
    {
        $this->contactTracker->method('getContact')->willReturn(new Lead());

        $this->ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $formSubscriber = new FormSubscriber(
            $this->emailModel,
            $this->leadModel,
            $this->contactTracker,
            $this->ipLookupHelper
        );

        $submission = new Submission();
        $submission->setForm(new Form());
        $submission->setLead(new Lead());

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $submissionEvent = new SubmissionEvent($submission, [], [], $request);

        $action = new Action();
        $action->setType('lead.pointschange');
        $action->setProperties(['points' => 1, 'operator' => 'plus']);
        $submissionEvent->setAction($action);

        $formSubscriber->onFormSubmitActionChangePoints($submissionEvent);

        $this->assertEquals(1, $submissionEvent->getSubmission()->getLead()->getPoints());
    }

    protected function triggeFormSubmitActionAddUtmTags(Request $request): void
    {
        $this->contactTracker->method('getContact')->willReturn(new Lead());

        $this->ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $formSubscriber = new FormSubscriber(
            $this->emailModel,
            $this->leadModel,
            $this->contactTracker,
            $this->ipLookupHelper
        );

        $submissionEvent = new SubmissionEvent($this->submission, [], [], $request);

        $action = new Action();
        $action->setType('lead.addutmtags');
        $submissionEvent->setAction($action);

        $formSubscriber->onFormSubmitActionAddUtmTags($submissionEvent);
    }
}
