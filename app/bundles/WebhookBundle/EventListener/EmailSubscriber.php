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

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_OPEN => ['onEmailOpen', 0],
        ];
    }

    public function onEmailOpen(EmailOpenEvent $event)
    {
        $types = [EmailEvents::EMAIL_ON_OPEN];

        $groups = ['statDetails', 'leadList', 'emailDetails'];
        $stat   = ($event->getStat());

        $payload = [
            'stat' => $stat,
        ];

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}
