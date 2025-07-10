<?php

namespace Mautic\PluginBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

class EventHelper
{
    use PushToIntegrationTrait;

    public static function pushLead($config, $lead, EntityManagerInterface $em, IntegrationHelper $integrationHelper): bool
    {
        $contact = $em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->getEntityWithPrimaryCompany($lead);

        static::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
