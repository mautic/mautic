<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\PageBundle\PageEvents;
use Mautic\PageBundle\Event\PageHitEvent;

/**
 * Class EmailSubscriber
 */
class PageSubscriber extends WebhookSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents ()
    {
        return array(
            PageEvents::PAGE_ON_HIT   => array('onPageHit', 0),
        );
    }

    public function onPageHit(PageHitEvent $event)
    {
        $types    = array(PageEvents::PAGE_ON_HIT);

        $groups = array('hitDetails', 'emailDetails', 'pageList', 'leadList');

        $hit = $event->getHit();

        $payload = array(
            'hit'  => $hit,
        );

        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $groups, true);
    }
}