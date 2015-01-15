<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class PointEventHelper
 *
 * @package Mautic\AddonBundle\Helper
 */
class PointEventHelper
{

    /**
     * @param               $lead
     * @param MauticFactory $factory
     */
    static public function pushLead($lead, MauticFactory $factory)
    {
        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $factory->getHelper('integration');

        $services = $integrationHelper->getIntegrationObjects(null, 'push_lead');

        $success = false;

        foreach ($services as $name => $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            if (method_exists($s, 'pushLead')) {
                if ($s->pushLead($lead)) {
                    $success = true;
                }
            }
        }

        return $success;
    }
}