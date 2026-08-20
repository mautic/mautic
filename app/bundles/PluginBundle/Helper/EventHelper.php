<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Helper;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

final class EventHelper
{
    use PushToIntegrationTrait;

    /**
     * @param array<string, mixed> $config
     */
    public function pushLead(array $config, $lead, LeadRepository $leadRepository, IntegrationHelper $integrationHelper): bool
    {
        $contact = $leadRepository->getEntityWithPrimaryCompany($lead);

        self::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return self::pushIt($config, $contact, $errors);
    }
}
