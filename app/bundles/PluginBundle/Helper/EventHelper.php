<?php

namespace Mautic\PluginBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

class EventHelper
{
    use PushToIntegrationTrait;

    /**
     * @param array<string, mixed> $config
     */
    public static function pushLead(array $config, $lead, EntityManagerInterface $em, IntegrationHelper $integrationHelper): bool
    {
        $contact = $em->getRepository(Lead::class)->getEntityWithPrimaryCompany($lead);

        static::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
