<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Form\Type\ChangeOwnerType;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    const ACTION_LEAD_CHANGE_OWNER = 'lead.changeowner';

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var ListModel
     */
    protected $listModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param LeadModel      $leadModel
     * @param FieldModel     $leadFieldModel
     * @param CompanyModel   $companyModel
     * @param CampaignModel  $campaignModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, LeadModel $leadModel, FieldModel $leadFieldModel, ListModel $listModel, CompanyModel $companyModel, CampaignModel $campaignModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->leadModel      = $leadModel;
        $this->leadFieldModel = $leadFieldModel;
        $this->listModel      = $listModel;
        $this->companyModel   = $companyModel;
        $this->campaignModel  = $campaignModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild', 0],
            LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION => [
                ['onCampaignTriggerActionChangePoints', 0],
                ['onCampaignTriggerActionChangeLists', 1],
                ['onCampaignTriggerActionUpdateLead', 2],
                ['onCampaignTriggerActionUpdateTags', 3],
                ['onCampaignTriggerActionAddToCompany', 4],
                ['onCampaignTriggerActionChangeCompanyScore', 4],
                ['onCampaignTriggerActionDeleteContact', 6],
                ['onCampaignTriggerActionChangeOwner', 7],
                ['onCampaignTriggerActionUpdateCompany', 8],
            ],
            LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerCondition', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        //Add actions
        $action = [
            'label'       => 'mautic.lead.lead.events.changepoints',
            'description' => 'mautic.lead.lead.events.changepoints_descr',
            'formType'    => 'leadpoints_action',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changepoints', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changelist',
            'description' => 'mautic.lead.lead.events.changelist_descr',
            'formType'    => 'leadlist_action',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changelist', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.updatelead',
            'description' => 'mautic.lead.lead.events.updatelead_descr',
            'formType'    => 'updatelead_action',
            'formTheme'   => 'MauticLeadBundle:FormTheme\ActionUpdateLead',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.updatelead', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.updatecompany',
            'description' => 'mautic.lead.lead.events.updatecompany_descr',
            'formType'    => 'updatecompany_action',
            'formTheme'   => 'MauticLeadBundle:FormTheme\ActionUpdateCompany',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.updatecompany', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changetags',
            'description' => 'mautic.lead.lead.events.changetags_descr',
            'formType'    => 'modify_lead_tags',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changetags', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.addtocompany',
            'description' => 'mautic.lead.lead.events.addtocompany_descr',
            'formType'    => 'addtocompany_action',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.addtocompany', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changeowner',
            'description' => 'mautic.lead.lead.events.changeowner_descr',
            'formType'    => ChangeOwnerType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction(self::ACTION_LEAD_CHANGE_OWNER, $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changecompanyscore',
            'description' => 'mautic.lead.lead.events.changecompanyscore_descr',
            'formType'    => 'scorecontactscompanies_action',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.scorecontactscompanies', $action);

        $trigger = [
            'label'                  => 'mautic.lead.lead.events.delete',
            'description'            => 'mautic.lead.lead.events.delete_descr',
            'eventName'              => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'connectionRestrictions' => [
                'target' => [
                    'decision'  => ['none'],
                    'action'    => ['none'],
                    'condition' => ['none'],
                ],
            ],
        ];
        $event->addAction('lead.deletecontact', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.field_value',
            'description' => 'mautic.lead.lead.events.field_value_descr',
            'formType'    => 'campaignevent_lead_field_value',
            'formTheme'   => 'MauticLeadBundle:FormTheme\FieldValueCondition',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];
        $event->addCondition('lead.field_value', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.device',
            'description' => 'mautic.lead.lead.events.device_descr',
            'formType'    => 'campaignevent_lead_device',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.device', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.tags',
            'description' => 'mautic.lead.lead.events.tags_descr',
            'formType'    => 'campaignevent_lead_tags',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];
        $event->addCondition('lead.tags', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.segments',
            'description' => 'mautic.lead.lead.events.segments_descr',
            'formType'    => 'campaignevent_lead_segments',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.segments', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.owner',
            'description' => 'mautic.lead.lead.events.owner_descr',
            'formType'    => 'campaignevent_lead_owner',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.owner', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.campaigns',
            'description' => 'mautic.lead.lead.events.campaigns_descr',
            'formType'    => 'campaignevent_lead_campaigns',
            'formTheme'   => 'MauticLeadBundle:FormTheme\ContactCampaignsCondition',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.campaigns', $trigger);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionChangePoints(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.changepoints')) {
            return;
        }

        $lead   = $event->getLead();
        $points = $event->getConfig()['points'];

        $somethingHappened = false;

        if ($lead !== null && !empty($points)) {
            $lead->adjustPoints($points);

            //add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($points);
            $log->setLead($lead);
            $log->setType('campaign');
            $log->setEventName("{$event->getEvent()['campaign']['id']}: {$event->getEvent()['campaign']['name']}");
            $log->setActionName("{$event->getEvent()['id']}: {$event->getEvent()['name']}");
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);

            $this->leadModel->saveEntity($lead);
            $somethingHappened = true;
        }

        return $event->setResult($somethingHappened);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionChangeLists(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.changelist')) {
            return;
        }

        $addTo      = $event->getConfig()['addToLists'];
        $removeFrom = $event->getConfig()['removeFromLists'];

        $lead              = $event->getLead();
        $somethingHappened = false;

        if (!empty($addTo)) {
            $this->leadModel->addToLists($lead, $addTo);
            $somethingHappened = true;
        }

        if (!empty($removeFrom)) {
            $this->leadModel->removeFromLists($lead, $removeFrom);
            $somethingHappened = true;
        }

        return $event->setResult($somethingHappened);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionUpdateLead(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.updatelead')) {
            return;
        }

        $lead = $event->getLead();

        $this->leadModel->setFieldValues($lead, $event->getConfig(), false);
        $this->leadModel->saveEntity($lead);

        return $event->setResult(true);
    }

    public function onCampaignTriggerActionChangeOwner(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext(self::ACTION_LEAD_CHANGE_OWNER)) {
            return;
        }

        $lead = $event->getLead();
        $data = $event->getConfig();
        if (empty($data['owner'])) {
            return;
        }

        $this->leadModel->updateLeadOwner($lead, $data['owner']);

        return $event->setResult(true);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionUpdateTags(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.changetags')) {
            return;
        }

        $config = $event->getConfig();
        $lead   = $event->getLead();

        $addTags    = (!empty($config['add_tags'])) ? $config['add_tags'] : [];
        $removeTags = (!empty($config['remove_tags'])) ? $config['remove_tags'] : [];

        $this->leadModel->modifyTags($lead, $addTags, $removeTags);

        return $event->setResult(true);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionAddToCompany(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.addtocompany')) {
            return;
        }

        $company           = $event->getConfig()['company'];
        $lead              = $event->getLead();
        $somethingHappened = false;

        if (!empty($company)) {
            $somethingHappened = $this->leadModel->addToCompany($lead, $company);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionChangeCompanyScore(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.scorecontactscompanies')) {
            return;
        }

        $score = $event->getConfig()['score'];
        $lead  = $event->getLead();

        if (!$this->leadModel->scoreContactsCompany($lead, $score)) {
            return $event->setFailed('mautic.lead.no_company');
        } else {
            return $event->setResult(true);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionDeleteContact(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.deletecontact')) {
            return;
        }

        $this->leadModel->deleteEntity($event->getLead());

        return $event->setResult(true);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionUpdateCompany(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.updatecompany')) {
            return;
        }

        $lead    = $event->getLead();
        $company = $lead->getPrimaryCompany();
        $config  = $event->getConfig();

        if (empty($company['id'])) {
            return;
        }

        $primaryCompany =  $this->companyModel->getEntity($company['id']);

        if (isset($config['companyname']) && $primaryCompany->getName() != $config['companyname']) {
            list($company, $leadAdded, $companyEntity) = IdentifyCompanyHelper::identifyLeadsCompany($config, $lead, $this->companyModel);
            if ($leadAdded) {
                $lead->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            } elseif ($companyEntity instanceof Company) {
                $this->companyModel->setFieldValues($companyEntity, $config);
                $this->companyModel->saveEntity($companyEntity);
            }

            if (!empty($company)) {
                // Save after the lead in for new leads created
                $this->companyModel->addLeadToCompany($companyEntity, $lead);
                $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
            }
        } else {
            $this->companyModel->setFieldValues($primaryCompany, $config, false);
            $this->companyModel->saveEntity($primaryCompany);
        }

        return $event->setResult(true);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerCondition(CampaignExecutionEvent $event)
    {
        $lead = $event->getLead();

        if (!$lead || !$lead->getId()) {
            return $event->setResult(false);
        }

        if ($event->checkContext('lead.device')) {
            $deviceRepo = $this->leadModel->getDeviceRepository();
            $result     = false;

            $deviceType   = $event->getConfig()['device_type'];
            $deviceBrands = $event->getConfig()['device_brand'];
            $deviceOs     = $event->getConfig()['device_os'];

            if (!empty($deviceType)) {
                $result = false;
                if (!empty($deviceRepo->getDevice($lead, $deviceType))) {
                    $result = true;
                }
            }

            if (!empty($deviceBrands)) {
                $result = false;
                if (!empty($deviceRepo->getDevice($lead, null, $deviceBrands))) {
                    $result = true;
                }
            }

            if (!empty($deviceOs)) {
                $result = false;
                if (!empty($deviceRepo->getDevice($lead, null, null, null, $deviceOs))) {
                    $result = true;
                }
            }
        } elseif ($event->checkContext('lead.tags')) {
            $tagRepo = $this->leadModel->getTagRepository();
            $result  = $tagRepo->checkLeadByTags($lead, $event->getConfig()['tags']);
        } elseif ($event->checkContext('lead.segments')) {
            $listRepo = $this->listModel->getRepository();
            $result   = $listRepo->checkLeadSegmentsByIds($lead, $event->getConfig()['segments']);
        } elseif ($event->checkContext('lead.owner')) {
            $result = $this->leadModel->getRepository()->checkLeadOwner($lead, $event->getConfig()['owner']);
        } elseif ($event->checkContext('lead.campaigns')) {
            $result = $this->campaignModel->getCampaignLeadRepository()->checkLeadInCampaigns($lead, $event->getConfig());
        } elseif ($event->checkContext('lead.field_value')) {
            if ($event->getConfig()['operator'] === 'date') {
                // Set the date in system timezone since this is triggered by cron
                $triggerDate = new \DateTime('now', new \DateTimeZone($this->params['default_timezone']));
                $interval    = substr($event->getConfig()['value'], 1); // remove 1st character + or -

                if (strpos($event->getConfig()['value'], '+P') !== false) { //add date
                    $triggerDate->add(new \DateInterval($interval)); //add the today date with interval
                    $result = $this->compareDateValue($lead, $event, $triggerDate);
                } elseif (strpos($event->getConfig()['value'], '-P') !== false) { //subtract date
                    $triggerDate->sub(new \DateInterval($interval)); //subtract the today date with interval
                    $result = $this->compareDateValue($lead, $event, $triggerDate);
                } elseif ($event->getConfig()['value'] === 'anniversary') {
                    /**
                     * note: currently mautic campaign only one time execution
                     * ( to integrate with: recursive campaign (future)).
                     */
                    $result = $this->leadFieldModel->getRepository()->compareDateMonthValue(
                            $lead->getId(), $event->getConfig()['field'], $triggerDate);
                }
            } else {
                $operators = $this->leadModel->getFilterExpressionFunctions();

                $result = $this->leadFieldModel->getRepository()->compareValue(
                        $lead->getId(),
                        $event->getConfig()['field'],
                        $event->getConfig()['value'],
                        $operators[$event->getConfig()['operator']]['expr']
                );
            }
        }

        return $event->setResult($result);
    }

    /**
     * Function to compare date value.
     *
     * @param Lead                   $lead
     * @param CampaignExecutionEvent $event
     * @param \DateTime              $triggerDate
     *
     * @return bool
     */
    private function compareDateValue(Lead $lead, CampaignExecutionEvent $event, \DateTime $triggerDate)
    {
        $result = $this->leadFieldModel->getRepository()->compareDateValue(
                $lead->getId(),
                $event->getConfig()['field'],
                $triggerDate->format('Y-m-d')
        );

        return $result;
    }
}
