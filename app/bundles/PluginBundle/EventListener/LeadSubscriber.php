<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class LeadSubscriber implements EventSubscriberInterface
{
    private const string FEATURE_PUSH_LEAD = 'push_lead';

    public function __construct(
        private IntegrationEntityRepository $integrationEntityRepository,
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

    public function onLeadDelete(LeadEvent $event): bool
    {
        $lead = $event->getLead();
        $this->integrationEntityRepository->findLeadsToDelete('lead%', $lead->getId());

        return false;
    }

    public function onCompanyDelete(CompanyEvent $event): bool
    {
        /** @var \Mautic\LeadBundle\Entity\Company $company */
        $company = $event->getCompany();
        $this->integrationEntityRepository->findLeadsToDelete('company%', $company->getId());

        return false;
    }

    /*
    * Change lead event
    */
    public function onLeadSave(LeadEvent $event): void
    {
        $lead = $event->getLead();
        if ($this->isAnyIntegrationEnabled()) {
            $this->integrationEntityRepository->updateErrorLeads('lead-error', $lead->getId());
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
