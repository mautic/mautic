<?php

namespace FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\EventListener\FormSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FormSubscriberTest extends TestCase
{
    private FormSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $auditLogModel        = $this->createMock(AuditLogModel::class);
        $mailer               = $this->createMock(MailHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $router               = $this->createMock(RouterInterface::class);

        $mailer->expects($this->once())
            ->method('getMailer')
            ->willReturnSelf();

        $this->subscriber = new FormSubscriber(
            $ipLookupHelper,
            $auditLogModel,
            $mailer,
            $coreParametersHelper,
            $translator,
            $router
        );
    }

    public function testOnFormSubmitActionRepost(): void
    {
        $postData = [
            'first_name' => "Test's Name",
            'notes'      => 'A & B < dy >',
            'formId'     => '1',
            'return'     => '',
            'formName'   => 'form190122',
            'messenger'  => '1',
        ];

        $resultData = [
            'first_name' => 'Test&#39;s Name',
            'notes'      => 'A &#38; B &#60; dy &#62;',
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
            'post_url'             => 'https://example.com',
            'failure_email'        => '',
            'authorization_header' => '',
        ];

        $action = new Action();

        return $action->setForm($this->getForm())
            ->setType('form.repost')
            ->setName('Test Action')
            ->setProperties($onSubmitActionConfig);
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
