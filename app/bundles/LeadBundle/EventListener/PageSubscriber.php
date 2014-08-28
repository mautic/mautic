<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class PageSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_HIT => array('onPageHit', 0)
        );
    }

    /**
     * Generate an anonymous lead from page hit
     *
     * @param PageHitEvent $event
     */
    public function onPageHit(PageHitEvent $event)
    {
        //check to see if this person is already tracked as a lead
        $hit        = $event->getHit();
        $trackingId = $hit->getTrackingId();

        $cookies = $event->getRequest()->cookies;
        $leadId  = $cookies->get($trackingId);
        $ip      = $hit->getIpAddress();
        if (empty($leadId)) {
            //this lead is not tracked yet so get leads by IP and track that lead or create a new one
            $model = $this->factory->getModel('lead.lead');
            $leads = $model->getLeadsByIp($ip->getIpAddress());

            if (count($leads)) {
                //just create a tracking cookie for the newest lead
                $leadId = $leads[0]->getId();
            } else {
                //let's create a lead
                $lead = new Lead();
                $lead->addIpAddress($ip);
                $model->saveEntity($lead);
                $leadId = $lead->getId();
            }
        }

        setcookie($trackingId, $leadId, time() + 1800);
    }
}