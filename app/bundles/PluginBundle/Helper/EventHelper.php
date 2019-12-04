<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\PluginBundle\EventListener\PushToIntegrationTrait;

/**
 * Class EventHelper.
 */
class EventHelper
{
    use PushToIntegrationTrait;

    /**
     * @param $config
     * @param                   $lead
     * @param EntityManager     $em
     * @param IntegrationHelper $integrationHelper
     *
     * @return bool
     */
    public static function pushLead($config, $lead, EntityManager $em, IntegrationHelper $integrationHelper)
    {
        $contact = $em->getRepository('MauticLeadBundle:Lead')->getEntityWithPrimaryCompany($lead);

        static::setStaticIntegrationHelper($integrationHelper);

        $errors  = [];

        return static::pushIt($config, $contact, $errors);
    }
}
