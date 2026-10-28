<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\CompanyPreDeleteEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadPostSaveEvent;
use Mautic\LeadBundle\Event\LeadPreDeleteEvent;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;

final readonly class LeadSubscriber
{
    private const string FEATURE_PUSH_LEAD = 'push_lead';
    public function __construct(
        private IntegrationEntityRepository $integrationEntityRepository,
        private IntegrationRepository $integrationRepository,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: LeadPreDeleteEvent::class,
        priority: 0
    )]
    public function onLeadDelete(LeadEvent $event): bool
    {
        $lead = $event->getLead();
        $this->integrationEntityRepository->findLeadsToDelete('lead%', $lead->getId());

        return false;
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: CompanyPreDeleteEvent::class,
        priority: 0
    )]
    public function onCompanyDelete(CompanyEvent $event): bool
    {
        /** @var Company $company */
        $company = $event->getCompany();
        $this->integrationEntityRepository->findLeadsToDelete('company%', $company->getId());

        return false;
    }
    /*
     * Change lead event
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: LeadPostSaveEvent::class,
        priority: 0
    )]
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
