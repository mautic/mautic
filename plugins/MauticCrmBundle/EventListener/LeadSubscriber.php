<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var LeadExport
     */
    protected $leadExport;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper, LeadExport $leadExport = null)
    {
        $this->integrationHelper = $integrationHelper;
        $this->leadExport        = $leadExport;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_PRE_DELETE     => ['onLeadPostDelete', 255],
            LeadEvents::LEAD_COMPANY_CHANGE => ['onLeadCompanyChange', 0],
        ];
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);

        if (false === $integrationObject || !$integrationObject->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $this->leadExport->setIntegration($integrationObject);
        $this->leadExport->update($event->getLead());
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);

        if (false === $integrationObject || !$integrationObject->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $this->leadExport->setIntegration($integrationObject);
        $this->leadExport->delete($event->getLead());
    }

    /**
     * @param Events\LeadChangeCompanyEvent $event
     */
    public function onLeadCompanyChange(Events\LeadChangeCompanyEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);

        if (false === $integrationObject || !$integrationObject->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $this->leadExport->setIntegration($integrationObject);
        $this->leadExport->update($event->getLead());
    }
}
