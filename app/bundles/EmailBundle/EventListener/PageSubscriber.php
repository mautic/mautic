<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * PageSubscriber constructor.
     *
     * @param EmailModel $emailModel
     */
    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_HIT => ['onPageHit', 0],
        ];
    }

    /**
     * Trigger point actions for page hits.
     *
     * @param Events\PageHitEvent $event
     */
    public function onPageHit(Events\PageHitEvent $event)
    {
        $hit      = $event->getHit();
        $redirect = $hit->getRedirect();

        if ($redirect && $hit->getEmail()) {
            // Check for an email stat
            $clickthrough = $event->getClickthroughData();

            if (isset($clickthrough['stat'])) {
                $emailStat = $this->emailModel->getEmailStatus($clickthrough['stat']);
            }
            if (empty($emailStat)) {
                if ($lead = $hit->getLead()) {
                    // Try searching by email and lead IDs
                    $stats = $this->emailModel->getEmailStati($hit->getSourceId(), $lead->getId());
                    if (count($stats)) {
                        $emailStat = $stats[0];
                    }
                }
            }
            if (!empty($emailStat)) {
                // Check to see if it has been marked as opened
                if (!$emailStat->isRead()) {
                    // Mark it as read
                    $this->emailModel->hitEmail($emailStat, $this->request);
                }

                if (!$emailStat->isClicked()) {
                    $this->emailModel->clickEmail($emailStat, $this->request);
                }
            }
        }
    }
}
