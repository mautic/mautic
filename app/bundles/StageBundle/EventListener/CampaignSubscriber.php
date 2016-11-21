<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Model\StageModel;
use Mautic\StageBundle\StageEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var StageModel
     */
    protected $stageModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param LeadModel  $leadModel
     * @param StageModel $stageModel
     */
    public function __construct(LeadModel $leadModel, StageModel $stageModel)
    {
        $this->leadModel  = $leadModel;
        $this->stageModel = $stageModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            StageEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerActionChangeStage', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'       => 'mautic.stage.campaign.event.change',
            'description' => 'mautic.stage.campaign.event.change_descr',
            'eventName'   => StageEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'    => 'stageaction_change',
            'formTheme'   => 'MauticStageBundle:FormTheme\StageActionChange',
        ];
        $event->addAction('stage.change', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionChangeStage(CampaignExecutionEvent $event)
    {
        $stageChange = false;
        $lead        = $event->getLead();
        $leadStage   = null;

        if ($lead instanceof Lead) {
            $leadStage = $lead->getStage();
        }

        $stageId         = (int) $event->getConfig()['stage'];
        $stageToChangeTo = $this->stageModel->getEntity($stageId);

        if ($stageToChangeTo != null && $stageToChangeTo->isPublished()) {
            if ($leadStage && $leadStage->getWeight() <= $stageToChangeTo->getWeight()) {
                $stageChange = true;
            } elseif (!$leadStage) {
                $stageChange = true;
            }
        }

        if ($stageChange) {
            $lead->stageChangeLogEntry(
                $stageToChangeTo,
                $stageToChangeTo->getId().': '.$stageToChangeTo->getName(),
                $event->getName()
            );
            $lead->setStage($stageToChangeTo);

            $this->leadModel->saveEntity($lead);
        }

        return $event->setResult($stageChange);
    }
}
