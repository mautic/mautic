<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace  MauticPlugin\MauticCrmBundle\Helper;

class IntegrationConfigHelper
{
    /**
     * @param array $config
     *
     * @return bool
     */
    public static function hasOverwriteWithBlank(array $config)
    {
        if (isset($config['overwriteWithBlank']) && isset($config['overwriteWithBlank'][0]) && $config['overwriteWithBlank'][0] == 'overwriteWithBlank') {
            return true;
        }

        return false;
    }
}
