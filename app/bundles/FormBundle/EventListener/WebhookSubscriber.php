<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\WebhookBundle\WebhookEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\FormEvents;

/**
 * Class WebhookSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class WebhookSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            WebhookEvents::WEBHOOK_ON_BUILD => array('onWebhookBuild', 0)
        );
    }

    /**
     * Add event triggers and actions
     *
     * @param WebhookBuilderEvent $event
     */
    public function onWebhookBuild(WebhookBuilderEvent $event)
    {
        // add checkbox to the webhook form for new leads
        $formSubmit = array(
            'label'       => 'mautic.form.webhook.event.form.submit',
            'description' => 'mautic.form.webhook.event.form.submit_desc',
        );

        // add it to the list
        $event->addEvent(FormEvents::FORM_ON_SUBMIT, $formSubmit);
    }
}