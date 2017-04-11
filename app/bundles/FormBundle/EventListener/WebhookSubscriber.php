<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\EventListener\WebhookModelTrait;
use Mautic\WebhookBundle\WebhookEvents;

/**
 * Class WebhookSubscriber.
 */
class WebhookSubscriber extends CommonSubscriber
{
    use WebhookModelTrait;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            WebhookEvents::WEBHOOK_ON_BUILD => ['onWebhookBuild', 0],
            FormEvents::FORM_ON_SUBMIT      => ['onFormSubmit', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param WebhookBuilderEvent $event
     */
    public function onWebhookBuild(WebhookBuilderEvent $event)
    {
        // add checkbox to the webhook form for new leads
        $formSubmit = [
            'label'       => 'mautic.form.webhook.event.form.submit',
            'description' => 'mautic.form.webhook.event.form.submit_desc',
        ];

        // add it to the list
        $event->addEvent(FormEvents::FORM_ON_SUBMIT, $formSubmit);
    }

    /**
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
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
