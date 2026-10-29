<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class WebhookSubscriber
{
    public function __construct(
        private WebhookModel $webhookModel,
    ) {
    }

    /**
     * Add event triggers and actions.
     */
    #[AsEventListener(
        event: WebhookBuilderEvent::class,
    )]
    public function onWebhookBuild(WebhookBuilderEvent $event): void
    {
        // add checkbox to the webhook form for new leads
        $formSubmit = [
            'label'       => 'mautic.form.webhook.event.form.submit',
            'description' => 'mautic.form.webhook.event.form.submit_desc',
        ];

        // add it to the list
        $event->addEvent(FormEvents::FORM_ON_SUBMIT, $formSubmit);
    }

    #[AsEventListener(
        event: SubmissionEvent::class,
    )]
    public function onFormSubmit(SubmissionEvent $event): void
    {
        $this->webhookModel->queueWebhooksByType(
            FormEvents::FORM_ON_SUBMIT,
            [
                'submission' => $event->getSubmission(),
            ],
            [
                'submissionDetails',
                'ipAddress',
                'leadList',
                'pageList',
                'formList',
            ]
        );
    }
}
