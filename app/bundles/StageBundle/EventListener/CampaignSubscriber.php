<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\StageBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\StageBundle\Model\StageModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\StageBundle\StageEvents;
use Mautic\StageBundle\Event\StageEvent;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\StageBundle\EventListener
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
     * @param MauticFactory $factory
     * @param LeadModel $leadModel
     * @param StageModel $stageModel
     */
    public function __construct(MauticFactory $factory, LeadModel $leadModel, StageModel $stageModel)
    {
        $this->leadModel = $leadModel;
        $this->stageModel = $stageModel;

        parent::__construct($factory);
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            StageEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0]
        );
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = array(
            'label'           => 'mautic.stage.campaign.event.change',
            'description'     => 'mautic.stage.campaign.event.change_descr',
            'eventName'       => StageEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'        => 'stagechange',
            'formTypeOptions' => array('update_select' => 'campaignevent_properties_stage'),
            'formTheme'       => 'MauticStageBundle:FormTheme\StageChange'
        );
        $event->addAction('stage.change', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $stageChange = false;
        $lead = $event->getLead();

        if ($lead instanceof Lead) {
            $fields = $lead->getFields();

            $leadCredentials       = $this->leadModel->flattenFields($fields);
            $leadCredentials['id'] = $lead->getId();
        } else {
            $leadCredentials = $lead;
        }

        $stageId = (int) $event->getConfig()['stage'];

        if (!empty($leadCredentials['stage']) && $leadCredentials['stage'] > $stageId) {
            $stageChange = true;
        }

        if ($stageChange){
            $stage = $this->stageModel->getEntity($stageId);

            if ($stage != null && $stage->isPublished()) {
                $this->factory->getModel('stage')->triggerAction('campaign.action', $event['campaign']['id']);
            }
        }

        return $event->setResult($stageChange);
    }
}