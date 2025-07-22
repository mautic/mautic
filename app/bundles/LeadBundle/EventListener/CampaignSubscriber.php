<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Form\Type\AddToCompanyActionType;
use Mautic\LeadBundle\Form\Type\CampaignConditionLeadPageHitType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadCampaignsType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadDeviceType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadDNCType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadFieldValueType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadOwnerType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadSegmentsType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadStagesType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadTagsType;
use Mautic\LeadBundle\Form\Type\CampaignEventPointType;
use Mautic\LeadBundle\Form\Type\ChangeOwnerType;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;
use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\Form\Type\PointActionType;
use Mautic\LeadBundle\Form\Type\UpdateCompanyActionType;
use Mautic\LeadBundle\Form\Type\UpdateLeadActionType;
use Mautic\LeadBundle\Helper\CustomFieldHelper;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Provider\FilterOperatorProvider;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\PointBundle\Model\PointGroupModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public const ACTION_LEAD_CHANGE_OWNER = 'lead.changeowner';

    private ?array $fields = null;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private LeadModel $leadModel,
        private FieldModel $leadFieldModel,
        private ListModel $listModel,
        private CompanyModel $companyModel,
        private CampaignModel $campaignModel,
        private CoreParametersHelper $coreParametersHelper,
        private DoNotContact $doNotContact,
        private PointGroupModel $groupModel,
        private FilterOperatorProvider $filterOperatorProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild', 0],
            LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION => [
                ['onCampaignTriggerActionChangePoints', 0],
                ['onCampaignTriggerActionChangeLists', 1],
                ['onCampaignTriggerActionUpdateTags', 3],
                ['onCampaignTriggerActionAddToCompany', 4],
                ['onCampaignTriggerActionChangeCompanyScore', 4],
                ['onCampaignTriggerActionChangeOwner', 7],
                ['onCampaignTriggerActionUpdateCompany', 8],
                ['onCampaignTriggerActionSetManipulator', 100],
            ],
            LeadEvents::ON_CAMPAIGN_BATCH_ACTION => [
                ['onCampaignTriggerActionUpdateLead', 0],
            ],
            LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerCondition', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     */
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        // Add actions
        $action = [
            'label'       => 'mautic.lead.lead.events.changepoints',
            'description' => 'mautic.lead.lead.events.changepoints_descr',
            'formType'    => PointActionType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changepoints', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changelist',
            'description' => 'mautic.lead.lead.events.changelist_descr',
            'formType'    => ListActionType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changelist', $action);

        $action = [
            'label'          => 'mautic.lead.lead.events.updatelead',
            'description'    => 'mautic.lead.lead.events.updatelead_descr',
            'formType'       => UpdateLeadActionType::class,
            'formTheme'      => '@MauticLead/FormTheme/ActionUpdateLead/_updatelead_action_widget.html.twig',
            'batchEventName' => LeadEvents::ON_CAMPAIGN_BATCH_ACTION,
        ];
        $event->addAction('lead.updatelead', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.updatecompany',
            'description' => 'mautic.lead.lead.events.updatecompany_descr',
            'formType'    => UpdateCompanyActionType::class,
            'formTheme'   => '@MauticLead/FormTheme/ActionUpdateCompany/_updatecompany_action_widget.html.twig',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.updatecompany', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.changetags',
            'description' => 'mautic.lead.lead.events.changetags_descr',
            'formType'    => ModifyLeadTagsType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.changetags', $action);

        $action = [
            'label'       => 'mautic.lead.lead.events.addtocompany',
            'description' => 'mautic.lead.lead.events.addtocompany_descr',
            'formType'    => AddToCompanyActionType::class,
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
            'formType'    => CompanyChangeScoreActionType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('lead.scorecontactscompanies', $action);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.field_value',
            'description' => 'mautic.lead.lead.events.field_value_descr',
            'formType'    => CampaignEventLeadFieldValueType::class,
            'formTheme'   => '@MauticLead/FormTheme/FieldValueCondition/_campaignevent_lead_field_value_widget.html.twig',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];
        $event->addCondition('lead.field_value', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.device',
            'description' => 'mautic.lead.lead.events.device_descr',
            'formType'    => CampaignEventLeadDeviceType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.device', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.pageHit',
            'description' => 'mautic.lead.lead.events.pageHit_descr',
            'formType'    => CampaignConditionLeadPageHitType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.pageHit', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.tags',
            'description' => 'mautic.lead.lead.events.tags_descr',
            'formType'    => CampaignEventLeadTagsType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];
        $event->addCondition('lead.tags', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.segments',
            'description' => 'mautic.lead.lead.events.segments_descr',
            'formType'    => CampaignEventLeadSegmentsType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.segments', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.stages',
            'description' => 'mautic.lead.lead.events.stages_descr',
            'formType'    => CampaignEventLeadStagesType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.stages', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.owner',
            'description' => 'mautic.lead.lead.events.owner_descr',
            'formType'    => CampaignEventLeadOwnerType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.owner', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.campaigns',
            'description' => 'mautic.lead.lead.events.campaigns_descr',
            'formType'    => CampaignEventLeadCampaignsType::class,
            'formTheme'   => '@MauticLead/FormTheme/ContactCampaignsCondition/_campaignevent_lead_campaigns_widget.html.twig',
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.campaigns', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.condition_donotcontact',
            'description' => 'mautic.lead.lead.events.condition_donotcontact_descr',
            'formType'    => CampaignEventLeadDNCType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.dnc', $trigger);

        $trigger = [
            'label'       => 'mautic.lead.lead.events.points',
            'description' => 'mautic.lead.lead.events.points_descr',
            'formType'    => CampaignEventPointType::class,
            'eventName'   => LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
        ];

        $event->addCondition('lead.points', $trigger);
    }

    public function onCampaignTriggerActionChangePoints(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.changepoints')) {
            return;
        }

        $lead              = $event->getLead();
        $points            = $event->getConfig()['points'];
        $somethingHappened = false;

        if (null !== $lead && !empty($points)) {
            $pointsLogActionName      = "{$event->getEvent()['id']}: {$event->getEvent()['name']}";
            $pointsLogEventName       = "{$event->getEvent()['campaign']['id']}: {$event->getEvent()['campaign']['name']}";
            $pointGroupId             = $event->getConfig()['group'] ?? null;
            $pointGroup               = $pointGroupId ? $this->groupModel->getEntity($pointGroupId) : null;

            if (!empty($pointGroup)) {
                $this->groupModel->adjustPoints($lead, $pointGroup, $points);
            } else {
                $lead->adjustPoints($points);
            }

            // add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($points);
            $log->setLead($lead);
            $log->setType('campaign');
            $log->setEventName($pointsLogEventName);
            $log->setActionName($pointsLogActionName);
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
            $log->setDateAdded(new \DateTime());
            if ($pointGroup) {
                $log->setGroup($pointGroup);
            }
            $lead->addPointsChangeLog($log);

            $this->leadModel->saveEntity($lead);
            $somethingHappened = true;
        }

        return $event->setResult($somethingHappened);
    }

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

    public function onCampaignTriggerActionUpdateLead(PendingEvent $event): void
    {
        $values = $event->getEvent()->getProperties();
        if (!$event->checkContext('lead.updatelead')) {
            return;
        }

        $logs = $event->getPending();

        foreach ($logs as $log) {
            $this->updateLead($log, $values, $event);
        }
    }

    /**
     * @param array<mixed> $values
     */
    private function updateLead(LeadEventLog $log, array $values, PendingEvent $event): void
    {
        $lead   = $log->getLead();
        $fields = $lead->getFields(true);
        try {
            $this->leadModel->setFieldValues($lead, CustomFieldHelper::fieldsValuesTransformer($fields, $values), false);
        } catch (ImportFailedException $e) {
            $event->fail($log, $e->getMessage());
        }

        foreach ($values as $alias => &$value) {
            if (isset($fields[$alias]) && 'boolean' === $fields[$alias]['type'] && 0 === $value) {
                // 0 is interpreted as 'don't change the bool field' instead of setting it to false, so we change the field manually in this step
                $lead->addUpdatedField($alias, 0);
            }
        }

        $this->leadModel->setFieldValues($lead, CustomFieldHelper::fieldsValuesTransformer($fields, $values), false);
        $this->leadModel->saveEntity($lead);

        $event->pass($log);
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

    public function onCampaignTriggerActionAddToCompany(CampaignExecutionEvent $event): void
    {
        if (!$event->checkContext('lead.addtocompany')) {
            return;
        }

        $company = $event->getConfig()['company'];
        $lead    = $event->getLead();

        if (!empty($company)) {
            $this->leadModel->addToCompany($lead, $company);
        }
    }

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
            [$company, $leadAdded, $companyEntity] = IdentifyCompanyHelper::identifyLeadsCompany($config, $lead, $this->companyModel);
            $companyChangeLog                      = null;
            if ($leadAdded) {
                $companyChangeLog = $lead->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            } elseif ($companyEntity instanceof Company) {
                $this->companyModel->setFieldValues($companyEntity, $config);
                $this->companyModel->saveEntity($companyEntity);
            }

            if (!empty($company)) {
                // Save after the lead in for new leads created
                $this->companyModel->addLeadToCompany($companyEntity, $lead);
                $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
            }

            if (null !== $companyChangeLog) {
                $this->companyModel->getCompanyLeadRepository()->detachEntity($companyChangeLog);
            }
        } else {
            $this->companyModel->setFieldValues($primaryCompany, $config, false);
            $this->companyModel->saveEntity($primaryCompany);
        }

        return $event->setResult(true);
    }

    public function onCampaignTriggerCondition(CampaignExecutionEvent $event)
    {
        $lead   = $event->getLead();
        $result = false;

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
        } elseif ($event->checkContext('lead.stages')) {
            $result   = $this->leadModel->getRepository()->isContactInOneOfStages($lead, $event->getConfig()['stages']);
        } elseif ($event->checkContext('lead.owner')) {
            $result = $this->leadModel->getRepository()->checkLeadOwner($lead, $event->getConfig()['owner']);
        } elseif ($event->checkContext('lead.campaigns')) {
            $result = $this->campaignModel->getCampaignLeadRepository()->checkLeadInCampaigns($lead, $event->getConfig());
        } elseif ($event->checkContext('lead.field_value')) {
            if ('date' === $event->getConfig()['operator']) {
                // Set the date in system timezone since this is triggered by cron
                $triggerDate = new \DateTime('now', new \DateTimeZone($this->coreParametersHelper->get('default_timezone')));
                $interval    = substr($event->getConfig()['value'], 1); // remove 1st character + or -

                if (str_contains($event->getConfig()['value'], '+P')) { // add date
                    $triggerDate->add(new \DateInterval($interval)); // add the today date with interval
                    $result = $this->compareDateValue($lead, $event, $triggerDate);
                } elseif (str_contains($event->getConfig()['value'], '-P')) { // subtract date
                    $triggerDate->sub(new \DateInterval($interval)); // subtract the today date with interval
                    $result = $this->compareDateValue($lead, $event, $triggerDate);
                } elseif ('anniversary' === $event->getConfig()['value']) {
                    /**
                     * note: currently mautic campaign only one time execution
                     * ( to integrate with: recursive campaign (future)).
                     */
                    $result = $this->leadFieldModel->getRepository()->compareDateMonthValue(
                        $lead->getId(), $event->getConfig()['field'], $triggerDate);
                }
            } else {
                $operators = OperatorOptions::getFilterExpressionFunctions();
                $field     = $event->getConfig()['field'];
                $value     = $event->getConfig()['value'];
                $fields    = $this->getFields($lead);

                $fieldValue = isset($fields[$field]) ? CustomFieldHelper::fieldValueTransfomer($fields[$field], $value) : $value;
                $result     = $this->leadFieldModel->getRepository()->compareValue(
                    $lead->getId(),
                    $field,
                    $fieldValue,
                    $operators[$event->getConfig()['operator']]['expr'],
                    $fields[$field]['type'] ?? null
                );
            }
        } elseif ($event->checkContext('lead.dnc')) {
            $channels  = $event->getConfig()['channels'];
            $reason    = $event->getConfig()['reason'] ?? null;
            foreach ($channels as $channel) {
                $isLeadDNC = $this->doNotContact->isContactable($lead, $channel);
                if (!empty($reason)) {
                    if ($isLeadDNC === $reason) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                } else {
                    if (0 !== $isLeadDNC) {
                        $result = true;
                    } else {
                        $result = false;
                    }
                }
            }
        } elseif ($event->checkContext('lead.pageHit')) {
            $startDate = $event->getConfig()['startDate'] ?? null;
            $endDate   = $event->getConfig()['endDate'] ?? null;
            $page      = $event->getConfig()['page'] ?? null;
            $url       = $event->getConfig()['page_url'] ?? null;

            $filter = [
                'search'        => '',
                'includeEvents' => [
                    0 => 'page.hit',
                ],
                'excludeEvents' => [],
            ];

            if ($startDate) {
                if (!is_a($startDate, 'DateTime')) {
                    $startDate = new \DateTime($startDate);
                }
                $filter['dateFrom'] = $startDate;
            }

            if ($endDate) {
                if (!is_a($endDate, 'DateTime')) {
                    $endDate = new \DateTime($endDate);
                }
                $filter['dateTo'] = $endDate->modify('+1 minutes');
            }

            $orderby = [
                0 => 'timestamp',
                1 => 'DESC',
            ];

            $leadTimeline       = $this->leadModel->getEngagements($lead, $filter, $orderby, 1, 255, false);
            $totalSpentTime     = $event->getConfig()['accumulative_time'] ?? null;
            $eventsLeadTimeline = $leadTimeline[0]['events'] ?? null;
            if (!empty($eventsLeadTimeline)) {
                foreach ($eventsLeadTimeline as $eventLeadTimeline) {
                    $hit        = $eventLeadTimeline['details']['hit'] ?? null;
                    $pageHitUrl = $hit['url'] ?? null;
                    $pageId     = $hit['page_id'] ?? null;

                    if (!empty($url)) {
                        $pageUrl = html_entity_decode($pageHitUrl);
                        if (fnmatch($url, $pageUrl)) {
                            if ($hit['dateLeft'] && $totalSpentTime) {
                                $realTotalSpentTime = (new \DateTime($hit['dateLeft']->format('Y-m-d H:i')))->getTimestamp() -
                                    (new \DateTime($hit['dateHit']->format('Y-m-d H:i')))->getTimestamp();
                                if ($realTotalSpentTime >= $totalSpentTime) {
                                    return $event->setResult(true);
                                }
                            } elseif (!$totalSpentTime) {
                                return $event->setResult(true);
                            }
                        }
                    }

                    if (!empty($page) && (int) $page === (int) $pageId) {
                        if ($hit['dateLeft'] && $totalSpentTime) {
                            $realTotalSpentTime = (new \DateTime($hit['dateLeft']->format('Y-m-d H:i')))->getTimestamp() -
                                (new \DateTime($hit['dateHit']->format('Y-m-d H:i')))->getTimestamp();
                            if ($realTotalSpentTime >= $totalSpentTime) {
                                return $event->setResult(true);
                            }
                        } elseif (!$totalSpentTime) {
                            return $event->setResult(true);
                        }
                    }
                }
            }
        } elseif ($event->checkContext('lead.points')) {
            $operators    = $this->filterOperatorProvider->getAllOperators();
            $group        = $event->getConfig()['group'] ?? null;
            $score        = $event->getConfig()['score'];
            $operatorExpr = $operators[$event->getConfig()['operator']]['expr'];

            if ($group) {
                $result = $this->leadModel->getGroupContactScoreRepository()->compareScore(
                    $lead->getId(), $group, $score, $operatorExpr,
                );
            } else {
                $result = $this->leadFieldModel->getRepository()->compareValue(
                    $lead->getId(), 'points', $score, $operatorExpr
                );
            }
        }

        return $event->setResult($result);
    }

    public function onCampaignTriggerActionSetManipulator(CampaignExecutionEvent $event): void
    {
        $lead = $event->getLead();

        if (!$lead instanceof Lead) {
            return;
        }

        $campaign      = $event->getLogEntry()->getCampaign();
        $campaignEvent = $event->getLogEntry()->getEvent();

        $lead->setManipulator(
            new LeadManipulator(
                'campaign',
                'trigger-action',
                $campaignEvent->getId(),
                sprintf('%s (%s)', $campaignEvent->getName(), $campaign->getName())
            )
        );
    }

    /**
     * Function to compare date value.
     */
    private function compareDateValue(Lead $lead, CampaignExecutionEvent $event, \DateTime $triggerDate): bool
    {
        return $this->leadFieldModel->getRepository()->compareDateValue(
            $lead->getId(),
            $event->getConfig()['field'],
            $triggerDate->format('Y-m-d')
        );
    }

    protected function getFields(Lead $lead): array
    {
        if (!$this->fields) {
            $contactFields = $lead->getFields(true);
            $companyFields = $this->leadFieldModel->getFieldListWithProperties('company');
            $this->fields  = array_merge($contactFields, $companyFields);
        }

        return $this->fields;
    }
}
