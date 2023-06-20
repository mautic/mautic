<?php

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\EventListener\FormSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormSubscriberTest extends TestCase
{
    /**
     * @var MockObject|EmailModel
     */
    protected $emailModel;

    /**
     * @var MockObject|ContactTracker
     */
    protected $contactTracker;

    /**
     * @var FormSubscriber
     */
    protected $formSubscriber;

    public function setUp(): void
    {
        $this->emailModel     = $this->createMock(EmailModel::class);
        $this->contactTracker = $this->createMock(ContactTracker::class);
        $this->formSubscriber = new FormSubscriber(
            $this->emailModel,
            $this->contactTracker
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetCurrentLeadWithLeadInFeedback(): void
    {
        $reflection = new \ReflectionMethod($this->formSubscriber, 'getCurrentLead');

        $reflection->setAccessible(true);

        $feedback    = ['lead.create' => ['lead' => ['email' => 'foobar']]];
        $currentLead = $reflection->invoke($this->formSubscriber, $feedback);

        $this->assertSame($feedback['lead.create']['lead'], $currentLead);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetCurrentLeadWithoutLeadInFeedback(): void
    {
        $reflection = new \ReflectionMethod($this->formSubscriber, 'getCurrentLead');

        $reflection->setAccessible(true);

        $contact = new Lead();
        $contact->setFirstname('Test');
        $contact->setLastname('User');

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($contact);

        $currentLead = $reflection->invoke($this->formSubscriber, []);

        $this->assertSame($contact->getProfileFields(), $currentLead);
    }

    public function testOnFormSubmitActionSendEmailWrongContext(): void
    {
        /** @var MockObject|SubmissionEvent $event */
        $event = $this->createMock(SubmissionEvent::class);

        $event->expects($this->exactly(2))
            ->method('checkContext')
            ->will($this->returnValueMap([
                ['email.send.user', false],
                ['email.send.lead', false],
            ]));

        $event->expects($this->exactly(0))
            ->method('getAction');

        $this->formSubscriber->onFormSubmitActionSendEmail($event);
    }

    public function testOnFormSubmitActionSendEmailUserContext(): void
    {
        /** @var MockObject|SubmissionEvent $event */
        $event = $this->createMock(SubmissionEvent::class);

        $event->expects($this->once())
            ->method('checkContext')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getTokens')
            ->willReturn([]);

        $event->expects($this->once())
            ->method('getActionFeedback')
            ->willReturn($feedback = ['lead.create' => ['lead' => ['email' => 'foobar']]]);

        $event->expects($this->once())
            ->method('getAction')
            ->willReturn($action = $this->createMock(Action::class));

        $action->expects($this->once())
            ->method('getProperties')
            ->willReturn(['user_id' => 1, 'email' => 1]);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($email = $this->createMock(Email::class));

        $email->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('sendEmailToUser');

        $this->formSubscriber->onFormSubmitActionSendEmail($event);
    }

    public function testOnFormSubmitActionSendEmailLeadContext(): void
    {
        /** @var MockObject|SubmissionEvent $event */
        $event = $this->createMock(SubmissionEvent::class);

        $event->expects($this->exactly(2))
            ->method('checkContext')
            ->will($this->returnValueMap([
                ['email.send.user', false],
                ['email.send.lead', true],
            ]));

        $event->expects($this->once())
            ->method('getTokens')
            ->willReturn([]);

        $event->expects($this->once())
            ->method('getActionFeedback')
            ->willReturn($feedback = ['lead.create' => ['lead' => ['email' => 'foobar']]]);

        $event->expects($this->exactly(2))
            ->method('getAction')
            ->willReturn($action = $this->createMock(Action::class));

        $action->expects($this->once())
            ->method('getProperties')
            ->willReturn(['email' => 1]);

        $action->expects($this->once())
            ->method('getForm')
            ->willReturn($this->createMock(Form::class));

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($email = $this->createMock(Email::class));

        $email->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('sendEmail');

        $this->formSubscriber->onFormSubmitActionSendEmail($event);
    }
}
