<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\PointEvents;

/**
 * Class PageSubscriber
 */
class PageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_HIT => array('onPageHit', 0)
        );
    }

    /**
     * Trigger point actions for page hits
     *
     * @param Events\PageHitEvent $event
     */
    public function onPageHit(Events\PageHitEvent $event)
    {
        $hit      = $event->getHit();
        $redirect = $hit->getRedirect();

        if ($redirect && $email = $hit->getEmail()) {
            // Check for an email stat
            /** @var \Mautic\EmailBundle\Model\EmailModel $model */
            $model = $this->factory->getModel('email');

            $clickthrough = $event->getClickthroughData();
            if (isset($clickthrough['stat'])) {
                $stat = $model->getEmailStatus($clickthrough['stat']);
            }

            if (empty($stat)) {
                if ($lead = $hit->getLead()) {
                    // Try searching by email and lead IDs
                    $stats = $model->getEmailStati($hit->getSourceId(), $lead->getId());
                    if (count($stats)) {
                        $stat = $stats[0];
                    }
                }
            }

            if (!empty($stat)) {
                // Check to see if it has been marked as opened
                if (!$stat->isRead()) {
                    // Mark it as read
                    $model->hitEmail($stat, $this->request);
                }
            }
        }
    }
}
