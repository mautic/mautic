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

use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class EmailSubscriber.
 */
class PageSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_HIT => ['onPageHit', 0],
        ];
    }

    public function onPageHit(PageHitEvent $event)
    {
        $types = [PageEvents::PAGE_ON_HIT];

        $groups = ['hitDetails', 'emailDetails', 'pageList', 'leadList'];

        $hit = $event->getHit();

        $payload = [
            'hit' => $hit,
        ];

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}
