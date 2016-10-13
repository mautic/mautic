<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

/**
 * Class PointActionHelper.
 */
class PointActionHelper
{
    /**
     * @param $eventDetails
     * @param $action
     *
     * @return bool
     */
    public static function validateAssetDownload($eventDetails, $action)
    {
        $assetId       = $eventDetails->getId();
        $limitToAssets = $action['properties']['assets'];

        if (!empty($limitToAssets) && !in_array($assetId, $limitToAssets)) {
            //no points change
            return false;
        }

        return true;
    }
}
