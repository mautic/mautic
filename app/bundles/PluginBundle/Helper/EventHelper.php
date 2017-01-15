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

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class EventHelper.
 */
class EventHelper
{
    /**
     * @param               $lead
     * @param MauticFactory $factory
     */
    public static function pushLead($config, $lead, MauticFactory $factory)
    {
        $contact = $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->getEntityWithPrimaryCompany($lead);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $factory->getHelper('integration');

        $integration = (!empty($config['integration'])) ? $config['integration'] : null;
        $feature     = (empty($integration)) ? 'push_lead' : null;

        $services = $integrationHelper->getIntegrationObjects($integration, $feature);
        $success  = false;

        foreach ($services as $name => $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            if (method_exists($s, 'pushLead')) {
                if ($s->pushLead($contact, $config)) {
                    $success = true;
                }
            }
        }

        return $success;
    }
}
