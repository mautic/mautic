<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

/**
 * Class EventHelper.
 */
class EventHelper
{
    use PushToIntegrationTrait;

    /**
     * @param $lead
     */
    public static function pushLead($config, $lead, MauticFactory $factory)
    {
        $contact = $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->getEntityWithPrimaryCompany($lead);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $factory->getHelper('integration');

        static::setStaticIntegrationHelper($integrationHelper);
        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
