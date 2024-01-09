<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginModel $pluginModel
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
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
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
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead                  = $event->getLead();
        $integrationEntityRepo = $this->pluginModel->getIntegrationEntityRepository();
        $integrationEntityRepo->updateErrorLeads('lead-error', $lead->getId());
    }
}
