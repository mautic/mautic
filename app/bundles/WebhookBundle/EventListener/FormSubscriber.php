<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Event\SubmissionEvent;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents ()
    {
        return array(
            FormEvents::FORM_ON_SUBMIT   => array('onFormSubmit', 0),
        );
    }

    /*
     * Form submit event
     *
     * @param SubmissionEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $types    = array(FormEvents::FORM_ON_SUBMIT);

        $groups = array('submissionDetails', 'ipAddress', 'leadList', 'pageList', 'formList');

        $form = $event->getSubmission();

        $payload = array(
            'submission'  => $form,
        );

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}