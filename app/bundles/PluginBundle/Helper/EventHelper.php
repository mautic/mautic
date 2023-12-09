<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

class EventHelper
{
    use PushToIntegrationTrait;

    public static function pushLead($config, $lead, MauticFactory $factory): bool
    {
        $contact = $factory->getEntityManager()->getRepository(Lead::class)->getEntityWithPrimaryCompany($lead);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $factory->getHelper('integration');

        static::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
