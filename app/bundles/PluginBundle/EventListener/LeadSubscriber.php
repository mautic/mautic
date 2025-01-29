<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    private const FEATURE_PUSH_LEAD = 'push_lead';

    public function __construct(
        private PluginModel $pluginModel,
        private IntegrationRepository $integrationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_PRE_DELETE    => ['onLeadDelete', 0],
            LeadEvents::LEAD_POST_SAVE     => ['onLeadSave', 0],
            LeadEvents::COMPANY_PRE_DELETE => ['onCompanyDelete', 0],
        ];
    }

    /*
     * Delete lead event
     */
    public function onLeadDelete(LeadEvent $event): bool
    {
        /** @var Lead $lead */
        $lead                  = $event->getLead();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        $integrationEntityRepo->findLeadsToDelete('lead%', $lead->getId());

        return false;
    }

    /*
     * Delete company event
     */
    public function onCompanyDelete(CompanyEvent $event): bool
    {
        /** @var \Mautic\LeadBundle\Entity\Company $company */
        $company               = $event->getCompany();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        $integrationEntityRepo->findLeadsToDelete('company%', $company->getId());

        return false;
    }

    /*
    * Change lead event
    */
    public function onLeadSave(LeadEvent $event): void
    {
        /** @var Lead $lead */
        $lead                  = $event->getLead();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        if ($this->isAnyIntegrationEnabled()) {
            $integrationEntityRepo->updateErrorLeads('lead-error', $lead->getId());
        }
    }

    private function isAnyIntegrationEnabled(): bool
    {
        $integrations = $this->integrationRepository->getIntegrations();
        foreach ($integrations as $integration) {
            /** @var Integration $integration */
            $supportedFeatures = $integration->getSupportedFeatures();

            if ($integration->getIsPublished() && !empty($integration->getApiKeys()) && in_array(self::FEATURE_PUSH_LEAD, $supportedFeatures)) {
                return true;
            }
        }

        return false;
    }
}
