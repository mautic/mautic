<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class LeadListsSubscriber.
 */
class LeadListSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $helper;

    protected $listModel;

    /**
     * ChannelSubscriber constructor.
     *
     * @param IntegrationHelper $helper
     */
    public function __construct(IntegrationHelper $helper, ListModel $listModel)
    {
        $this->helper    = $helper;
        $this->listModel = $listModel;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onFilterChoiceFieldsGenerate', 0],
           // LeadEvents::LEAD_LIST_BATCH_CHANGE => ['onLeadListBatchChange', 0],
        ];
    }

    /**
     * @param LeadListFiltersChoicesEvent $event
     */
    public function onFilterChoiceFieldsGenerate(LeadListFiltersChoicesEvent $event)
    {
        $integration = $this->helper->getIntegrationObject('Salesforce');
        $choices     = [];
        $campaigns   = $integration->getCampaigns();
        if (isset($campaigns['records']) && !empty($campaigns['records'])) {
            foreach ($campaigns['records'] as $campaign) {
                $choices[$campaign['Id']] = $campaign['Name'];
            }
        }
        if (!empty($campaigns)) {
            $config = [
                'label'      => $this->translator->trans('mautic.plugin.integration.campaign_members'),
                'properties' => ['type' => 'select', 'list' => $choices],
                'operators'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                        ],
                    ]),
                'object' => 'lead',
            ];
            $event->addChoice('lead', 'integration_campaigns', $config);
        }
    }

    /**
     * Add/remove contacts to a segment based on contacts found in Integration Campaigns.
     *
     * @param ListChangeEvent $event
     */
    public function onLeadListBatchChange(ListChangeEvent $event)
    {
        //get Integration Campaign members
        $integrationObjects = $this->helper->getIntegrationObjects();
        $list               = $event->getList();

        foreach ($integrationObjects as $name => $integrationObject) {
            $settings = $integrationObject->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            if (method_exists($integrationObject, 'getCampaignMembers')) {
                if ($integrationObject->getCampaignMembers($list)) {
                    $success = true;
                }
            }
        }
    }
}
