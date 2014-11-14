<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

/**
 * Class PointActionHelper
 *
 * @package Mautic\AssetBundle\Helper
 */
class PointActionHelper
{

    /**
     * @param $eventDetails
     * @param $action
     *
     * @return int
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