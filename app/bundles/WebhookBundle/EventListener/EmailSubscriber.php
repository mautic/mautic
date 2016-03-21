<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;

/**
 * Class EmailSubscriber
 */
class EmailSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents ()
    {
        return array(
            EmailEvents::EMAIL_ON_OPEN   => array('onEmailOpen', 0),
        );
    }

    public function onEmailOpen(EmailOpenEvent $event)
    {
        $types    = array(EmailEvents::EMAIL_ON_OPEN);

        $groups = array('statDetails', 'leadList', 'emailDetails');
        $stat = ($event->getStat());

        $payload = array(
            'stat'  => $stat,
        );

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}