<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreDelete;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\CompanyPreDeleteEvent;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LeadSubscriber
{
    private const string FEATURE_PUSH_LEAD = 'push_lead';

    public function __construct(
        private IntegrationEntityRepository $integrationEntityRepository,
        private IntegrationRepository $integrationRepository,
    ) {
    }

    #[AsEventListener]
    public function onLeadDelete(EntityPreDelete $event): bool
    {
        $lead = $event->getEntity();
        if (!$lead instanceof Lead) {
            return false;
        }

        $this->integrationEntityRepository->findLeadsToDelete('lead%', $lead->getId());

        return false;
    }

    #[AsEventListener(
        event: CompanyPreDeleteEvent::class,
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
    #[AsEventListener]
    public function onLeadSave(EntityPostSave $event): void
    {
        $lead = $event->getEntity();
        if (!$lead instanceof Lead) {
            return;
        }

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
