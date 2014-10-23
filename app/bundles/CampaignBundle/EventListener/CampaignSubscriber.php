<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

//todo - subscribe to lead delete event to remove records pertaining to the specific lead

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_POST_SAVE     => array('onCampaignPostSave', 0),
            CampaignEvents::CAMPAIGN_POST_DELETE   => array('onCampaignDelete', 0),
            CampaignEvents::CAMPAIGN_ON_BUILD      => array('onCampaignBuild', 0),
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE => array('onCampaignLeadChange', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignPostSave(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "campaign",
                "object"    => "campaign",
                "objectId"  => $campaign->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignDelete(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $log = array(
            "bundle"     => "campaign",
            "object"     => "campaign",
            "objectId"   => $campaign->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $campaign->getName()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Add event triggers and actions
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        //Add trigger
        $leadChangeTrigger = array(
            'group'        => 'mautic.campaign.event.action.group',
            'label'        => 'mautic.campaign.trigger.leadchange',
            'description'  => 'mautic.campaign.trigger.leadchange_descr',
            'formType'     => 'campaigntrigger_leadchange',
            'callback'     => '\Mautic\CampaignBundle\Helper\CampaignEventHelper::validateLeadChangeTrigger'
        );
        $event->addTrigger('campaign.leadchange', $leadChangeTrigger);

        //Add action to actually add/remove lead to a specific lists
        $addRemoveLeadAction = array(
            'group'        => 'mautic.campaign.event.action.group',
            'label'        => 'mautic.campaign.action.addremovelead',
            'description'  => 'mautic.campaign.action.addremovelead_descr',
            'formType'     => 'campaignaction_addremovelead',
            'callback'     => '\Mautic\CampaignBundle\Helper\CampaignEventHelper::addRemoveLead'
        );
        $event->addAction('campaign.addremovelead', $addRemoveLeadAction);
    }

    /**
     * @param Events\CampaignLeadChangeEvent $event
     */
    public function onCampaignLeadChange(Events\CampaignLeadChangeEvent $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $model */
        $model    = $this->factory->getModel('campaign');
        $lead     = $event->getLead();
        $campaign = $event->getCampaign();
        $model->triggerEvent('campaign.leadchange', $event, 'campaign.leadchange.'.$lead->getId() . '.' . $campaign->getId());
    }

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey = 'campaign.triggered';
        $eventTypeName = $this->translator->trans('mautic.campaign.event.triggered');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        $filter = $event->getEventFilter();
        $loadAllEvents = !isset($filter[0]);
        $eventFilterExists = in_array($eventTypeKey, $filter);

        if (!$loadAllEvents && !$eventFilterExists) {
            return;
        }

        $lead    = $event->getLead();
        $leadIps = array();

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $leadIps[] = $ip->getId();
        }

        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $logRepository */
        $logRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');

        $logs = $logRepository->getLeadLogs($lead->getId(), $leadIps);

        // Add the hits to the event array
        foreach ($logs as $log) {
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel' => $eventTypeName,
                'timestamp' => $log['dateTriggered'],
                'extra'     => array(
                    'log' => $log
                ),
                'contentTemplate' => 'MauticCampaignBundle:Timeline:index.html.php'
            ));
        }
    }
}