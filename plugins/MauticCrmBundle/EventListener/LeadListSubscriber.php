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
        ];
    }

    /**
     * @param LeadListFiltersChoicesEvent $event
     */
    public function onFilterChoiceFieldsGenerate(LeadListFiltersChoicesEvent $event)
    {
        $integration = $this->helper->getIntegrationObject('Salesforce');
        $campaigns   = $integration->getCampaigns();
        if (!empty($campaigns)) {
            $config = [
                'label'      => $this->translator->trans('mautic.plugin.integration.campaign_members'),
                'properties' => ['type' => 'integrationcampaign_filter', 'options' => [$campaigns]],
                'operators'  => $this->listModel->getOperatorsForFieldType('default'),
                'object'     => 'lead',
            ];
            $event->addChoice('lead', 'Campaign members', $config);
        }
    }
}
