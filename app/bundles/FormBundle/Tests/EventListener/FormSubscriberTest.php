<?php

namespace FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\EventListener\FormSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormSubscriberTest extends TestCase
{
    private FormSubscriber $subscriber;

    /**
     * @var MailHelper&MockObject
     */
    private MailHelper $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->mailer         = $this->createMock(MailHelper::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $router               = $this->createMock(RouterInterface::class);
        $languageHelper       = $this->createMock(LanguageHelper::class);

        $this->mailer->expects($this->once())
            ->method('getMailer')
            ->willReturnSelf();

        $this->subscriber = new FormSubscriber(
            $ipLookupHelper,
            $auditLogModel,
            $this->mailer,
            $translator,
            $router,
            $languageHelper
        );
    }

    public function testOnFormSubmitActionRepost(): void
    {
        $postData = [
            'first_name' => "Test's Name un être> and être",
            'notes'      => 'A & B < dy >
New line',
            'formId'     => '1',
            'return'     => '',
            'formName'   => 'form190122',
            'messenger'  => '1',
        ];

        $resultData = [
            'first_name' => 'Test&#39;s Name un &ecirc;tre&gt; and être',
            'notes'      => 'A &#38; B &#60; dy &#62;&#10;New line',
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $submissionEvent = new SubmissionEvent($submission, $postData, $request->server, $request);
        $submissionEvent->setResults($resultData)
            ->setFields($this->getFormFields())
            ->setAction($this->getFormRepostAction());

        $this->subscriber->onFormSubmitActionRepost($submissionEvent);
        $postPayload = $submissionEvent->getPostSubmitPayload();

        $this->assertSame([
            $postData['first_name'],
            $postData['notes'],
        ], [
            $postPayload['first_name'],
            $postPayload['notes'],
        ], 'Form data should be decode before posting to next form');
    }

    public function testOnFormSubmitSendsNothingIfNoEmailsWereSet(): void
    {
        $tokensData = [];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'        => null,
            'cc'        => null,
            'bcc'       => null,
            'copy_lead' => false,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::never())
            ->method('send');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    /**
     * @dataProvider toCcBccProvider
     */
    public function testOnFormSubmitSendsIfOneOfEmailsEmailsWereSet(?string $to, ?string $cc, ?string $bcc): void
    {
        $subject    = 'subject';
        $message    = 'message';
        $tokensData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => "A &#38; B \n &#60; dy &#62;",
        ];

        $emailTokens = [
            'first_name' => "Test's Name",
            'notes'      => "A & B <br />\n < dy >",
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'        => $to,
            'cc'        => $cc,
            'bcc'       => $bcc,
            'copy_lead' => false,
            'subject'   => $subject,
            'message'   => $message,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::once())
            ->method('reset');
        $this->mailer->expects(self::once())
            ->method('send');

        if (null !== $to) {
            $this->mailer->expects(self::once())
                ->method('setTo')
                ->with(array_fill_keys(array_map('trim', explode(',', $to)), null));
        }

        if (null !== $cc) {
            $this->mailer->expects(self::once())
                ->method('setCc')
                ->with(array_fill_keys(array_map('trim', explode(',', $cc)), null));
        }

        if (null !== $bcc) {
            $this->mailer->expects(self::once())
                ->method('setBcc')
                ->with(array_fill_keys(array_map('trim', explode(',', $bcc)), null));
        }

        $this->mailer->expects(self::once())
            ->method('setSubject')
            ->with($subject);
        $this->mailer->expects(self::once())
            ->method('setBody')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('parsePlainText')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('addTokens')
            ->with($emailTokens);

        $this->mailer->expects(self::once())
            ->method('setLead');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    public static function toCcBccProvider(): \Generator
    {
        yield ['to@email.email, to2@email.email', null, null];
        yield [null, 'cc@email.email, cc2@email.email', null];
        yield [null, null, 'bcc@email.email, bcc2@email.email'];
    }

    public function testOnFormSubmitSendsIfCcAndBccWereSet(): void
    {
        $cc         = 'cc@email.email, cc2@email.email';
        $bcc        = 'bcc@email.email, bcc@email.email'; // same emails will produce single email address
        $subject    = 'subject';
        $message    = 'message';
        $tokensData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => "A &#38; B \n &#60; dy &#62;",
        ];

        $emailTokens = [
            'first_name' => "Test's Name",
            'notes'      => "A & B <br />\n < dy >",
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'             => null,
            'cc'             => $cc,
            'bcc'            => $bcc,
            'copy_lead'      => false,
            'email_to_owner' => false,
            'subject'        => $subject,
            'message'        => $message,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::once())
            ->method('reset');
        $this->mailer->expects(self::once())
            ->method('send');

        $this->mailer->expects(self::never())
            ->method('setTo');
        $this->mailer->expects(self::once())
            ->method('setCc')
            ->with(array_fill_keys(array_map('trim', explode(',', $cc)), null));
        $this->mailer->expects(self::once())
            ->method('setBcc')
            ->with(array_fill_keys(array_map('trim', explode(',', $bcc)), null));
        $this->mailer->expects(self::once())
            ->method('setSubject')
            ->with($subject);
        $this->mailer->expects(self::once())
            ->method('setBody')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('parsePlainText')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('addTokens')
            ->with($emailTokens);

        $this->mailer->expects(self::once())
            ->method('setLead');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    public function testOnFormSubmitSendsIfCopyLeadEmailWasSet(): void
    {
        $subject    = 'subject';
        $message    = 'message';
        $leadEmail  = 'lead@email.email';
        $tokensData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => "A &#38; B \n &#60; dy &#62;",
        ];

        $emailTokens = [
            'first_name' => "Test's Name",
            'notes'      => "A & B <br />\n < dy >",
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $submission->getLead()->setEmail($leadEmail);
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'        => null,
            'cc'        => null,
            'bcc'       => null,
            'copy_lead' => true,
            'subject'   => $subject,
            'message'   => $message,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::once())
            ->method('reset');
        $this->mailer->expects(self::once())
            ->method('send');

        $this->mailer->expects(self::once())
            ->method('setTo')
            ->with([$leadEmail => null]);
        $this->mailer->expects(self::once())
            ->method('setSubject')
            ->with($subject);
        $this->mailer->expects(self::once())
            ->method('setBody')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('parsePlainText')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('addTokens')
            ->with($emailTokens);

        $this->mailer->expects(self::once())
            ->method('setLead');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    public function testOnFormSubmitSendsIfEmailToOwnerEmailWasSet(): void
    {
        $subject    = 'subject';
        $message    = 'message';
        $ownerEmail = 'lead@email.email';
        $tokensData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => "A &#38; B \n &#60; dy &#62;",
        ];

        $emailTokens = [
            'first_name' => "Test's Name",
            'notes'      => "A & B <br />\n < dy >",
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $owner           = new User();
        $owner->setEmail($ownerEmail);
        $submission->getLead()->setOwner($owner);
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'             => null,
            'cc'             => null,
            'bcc'            => null,
            'copy_lead'      => false,
            'email_to_owner' => true,
            'subject'        => $subject,
            'message'        => $message,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::once())
            ->method('reset');
        $this->mailer->expects(self::once())
            ->method('send');

        $this->mailer->expects(self::once())
            ->method('setTo')
            ->with([$ownerEmail => null]);
        $this->mailer->expects(self::once())
            ->method('setSubject')
            ->with($subject);
        $this->mailer->expects(self::once())
            ->method('setBody')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('parsePlainText')
            ->with($message);
        $this->mailer->expects(self::once())
            ->method('addTokens')
            ->with($emailTokens);

        $this->mailer->expects(self::once())
            ->method('setLead');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    public function testOnFormSubmitSendsIfAllWereSet(): void
    {
        $to         = 'to@email.email';
        $cc         = 'cc@email.email, cc2@email.email';
        $bcc        = 'bcc@email.email';
        $leadEmail  = 'lead@email.email';
        $ownerEmail = 'owner@email.email';
        $subject    = 'subject';
        $message    = 'message';
        $tokensData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => "A &#38; B \n &#60; dy &#62;",
        ];

        $emailTokens = [
            'first_name' => "Test's Name",
            'notes'      => "A & B <br />\n < dy >",
        ];

        $request         = new Request();
        $submission      = $this->getFormSubmission();
        $owner           = new User();
        $owner->setEmail($ownerEmail);
        $submission->getLead()->setOwner($owner);
        $submission->getLead()->setEmail($leadEmail);
        $submissionEvent = new SubmissionEvent($submission, [], $request->server, $request);
        $action          = $this->getFormSubmitActionSendEmail();
        $action->setProperties([
            'to'             => $to,
            'cc'             => $cc,
            'bcc'            => $bcc,
            'copy_lead'      => true,
            'email_to_owner' => true,
            'subject'        => $subject,
            'message'        => $message,
        ]);
        $submissionEvent->setTokens($tokensData)
            ->setFields($this->getFormFields())
            ->setAction($action);

        $this->mailer->expects(self::exactly(3))
            ->method('reset');
        $this->mailer->expects(self::exactly(3))
            ->method('send');

        $this->mailer->expects(self::exactly(3))
            ->method('setTo')
            ->withConsecutive(
                [[$to => null]],
                [[$leadEmail => null]],
                [[$ownerEmail => null]]
            );
        $this->mailer->expects(self::once())
            ->method('setCc')
            ->with(array_fill_keys(array_map('trim', explode(',', $cc)), null));
        $this->mailer->expects(self::once())
            ->method('setBcc')
            ->with([$bcc => null]);
        $this->mailer->expects(self::exactly(3))
            ->method('setSubject')
            ->with($subject);
        $this->mailer->expects(self::exactly(3))
            ->method('setBody')
            ->with($message);
        $this->mailer->expects(self::exactly(3))
            ->method('parsePlainText')
            ->with($message);
        $this->mailer->expects(self::exactly(3))
            ->method('addTokens')
            ->with($emailTokens);

        $this->mailer->expects(self::exactly(3))
            ->method('setLead');

        $this->subscriber->onFormSubmitActionSendEmail($submissionEvent);
    }

    /**
     * @return string[][]
     */
    private function getFormFields(): array
    {
        return [
            1 => [
                'id'    => '1',
                'type'  => 'text',
                'alias' => 'first_name',
            ],
            2 => [
                'id'    => '2',
                'type'  => 'text',
                'alias' => 'notes',
            ],
            3 => [
                'id'    => '3',
                'type'  => 'button',
                'alias' => 'submit',
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('test_form');
        $form->setFormType('standalone');

        return $form;
    }

    private function getFormRepostAction(): Action
    {
        $onSubmitActionConfig = [
            'post_url'             => 'https://mautic.org',
            'failure_email'        => '',
            'authorization_header' => '',
        ];

        $action = new Action();

        return $action->setForm($this->getForm())
            ->setType('form.repost')
            ->setName('Test Action')
            ->setProperties($onSubmitActionConfig);
    }

    private function getFormSubmitActionSendEmail(): Action
    {
        $action = new Action();

        return $action->setForm($this->getForm())
            ->setType('form.email')
            ->setName('Test Action');
    }

    private function getFormSubmission(): Submission
    {
        $lead = new Lead();
        $lead->setFields($this->getFormFields());

        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');

        $submission = new Submission();

        return $submission->setForm($this->getForm())
            ->setLead($lead)
            ->setIpAddress($ipAddress);
    }
}
