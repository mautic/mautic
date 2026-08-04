<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

final class EventHelper
{
    use PushToIntegrationTrait;

    /**
     * @param array<string, mixed> $config
     */
    public static function pushLead(array $config, $lead, LeadRepository $leadRepository, IntegrationHelper $integrationHelper): bool
    {
        $contact = $leadRepository->getEntityWithPrimaryCompany($lead);

        static::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
