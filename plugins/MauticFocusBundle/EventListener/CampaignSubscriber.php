<?php

/*
 * @copyright   2017 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticFocusBundle\Entity as FocusEntity;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var FocusModel
     */
    protected $focusModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param EventModel $eventModel
     * @param FocusModel $focusModel
     */
    public function __construct(EventModel $eventModel, FocusModel $focusModel)
    {
        $this->campaignEventModel = $eventModel;
        $this->focusModel         = $focusModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            FocusEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDescision', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'           => 'mautic.focus.campaign.event.show_focus',
            'description'     => 'mautic.focus.campaign.event.show_focus_descr',
            'eventName'       => FocusEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'formType'        => 'focusshow_list',
            'formTypeOptions' => ['update_select' => 'campaignevent_properties_focus'],
        ];
        $event->addDecision('focus.show', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $currentEvent = $event->getEvent();
        if ($currentEvent['properties']['focus'] > 0) {
            /**
             * log leads, campaign in to focus_campaign table.
             */
            $entity = new FocusEntity\FocusCampaign();
            $entity->setCampaign($this->em->getReference(Campaign::class, $currentEvent['campaign']['id']));
            $entity->setFocus($this->em->getReference(FocusEntity\Focus::class, $currentEvent['properties']['focus']));
            $entity->setLead($event->getLead());
            $entity->setLeadEventLog($event->getLogEntry());
            $this->focusModel->getFocusCampaignRepository()->saveEntity($entity);

            /*
             * Set current LeadEventLog as failed (log to LeadEventFailedLog), since
             * event completion is based on action from focus page
             */
            $this->campaignEventModel->setEventStatus($event->getLogEntry(), false, 'No user interaction');

            $focus = $this->focusModel->getEntity($currentEvent['properties']['focus']);
            $this->focusModel->saveEntity($focus);
        }
    }
}