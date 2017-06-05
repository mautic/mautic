<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Model\PluginModel;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var PluginModel
     */
    protected $pluginModel;

    /**
     * LeadSubscriber constructor.
     *
     * @param PluginModel $pluginModel
     */
    public function __construct(PluginModel $pluginModel)
    {
        $this->pluginModel = $pluginModel;
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_PRE_DELETE => ['onLeadDelete', 0],
            LeadEvents::LEAD_POST_SAVE  => ['onLeadSave', 0],
        ];
    }

    /*
     * Delete lead event
     */
    public function onLeadDelete(LeadEvent $event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead                  = $event->getLead();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        $integrationEntityRepo->findLeadsToDelete('lead%', $lead->getId());
        $success = false;

        return $success;
    }

    /*
    * Change lead event
    */
    public function onLeadSave(LeadEvent $event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead                  = $event->getLead();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        $integrationEntityRepo->updateErrorLeads('lead-error', $lead->getId());
    }
}
