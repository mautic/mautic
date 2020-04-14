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

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\ListPreProcessListEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadListSubscriber implements EventSubscriberInterface
{
    /**
     * @var IntegrationHelper
     */
    private $helper;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(IntegrationHelper $helper, ListModel $listModel, TranslatorInterface $translator)
    {
        $this->helper     = $helper;
        $this->listModel  = $listModel;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onFilterChoiceFieldsGenerate', 0],
            LeadEvents::LIST_PRE_PROCESS_LIST            => ['onLeadListProcessList', 0],
        ];
    }

    public function onFilterChoiceFieldsGenerate(LeadListFiltersChoicesEvent $event)
    {
        $services = $this->helper->getIntegrationObjects();
        $choices  = [];

        /** @var CrmAbstractIntegration $integration */
        foreach ($services as $integration) {
            if (!$integration || !$integration->getIntegrationSettings()->isPublished()) {
                continue;
            }

            if (method_exists($integration, 'getCampaigns')) {
                $integrationChoices = $integration->getCampaignChoices();
                if ($integrationChoices) {
                    $integrationName = $integration->getName();
                    // Keep BC with pre-2.11.0 that only supported SF campaigns
                    if ('Salesforce' !== $integrationName) {
                        array_walk(
                            $integrationChoices,
                            function (&$choice) use ($integrationName) {
                                $choice['value'] = $integrationName.'::'.$choice['value'];
                            }
                        );
                    }

                    $choices[$integration->getDisplayName()] = $integrationChoices;
                }
            }
        }

        if (!empty($choices)) {
            $config = [
                'label'      => $this->translator->trans('mautic.plugin.integration.campaign_members'),
                'properties' => ['type' => 'select', 'list' => $choices],
                'operators'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                        ],
                    ]
                ),
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
    public function onLeadListProcessList(ListPreProcessListEvent $event)
    {
        //get Integration Campaign members
        $list    = $event->getList();
        $success = false;
        $filters = ($list instanceof LeadList) ? $list->getFilters() : $list['filters'];

        foreach ($filters as $filter) {
            if ('integration_campaigns' == $filter['field']) {
                if (false !== strpos($filter['filter'], '::')) {
                    list($integrationName, $campaignId) = explode('::', $filter['filter']);
                } else {
                    // Assuming this is a Salesforce integration for BC with pre 2.11.0
                    $integrationName = 'Salesforce';
                    $campaignId      = $filter['filter'];
                }

                /** @var CrmAbstractIntegration $integrationObject */
                if ($integrationObject = $this->helper->getIntegrationObject($integrationName)) {
                    if (!$integrationObject->getIntegrationSettings()->isPublished()) {
                        continue;
                    }

                    if (method_exists($integrationObject, 'getCampaignMembers')) {
                        if ($integrationObject->getCampaignMembers($campaignId)) {
                            $success = true;
                        }
                    }
                }
            }
        }

        return $event->setResult($success);
    }
}
