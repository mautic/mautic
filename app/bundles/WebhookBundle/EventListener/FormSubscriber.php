<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_SUBMIT => ['onFormSubmit', 0],
        ];
    }

    /*
     * Form submit event
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $types = [FormEvents::FORM_ON_SUBMIT];

        $groups = ['submissionDetails', 'ipAddress', 'leadList', 'pageList', 'formList'];

        $form = $event->getSubmission();

        $payload = [
            'submission' => $form,
        ];

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}
