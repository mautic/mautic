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
 * Class CampaignTriggerHelper
 *
 * @package Mautic\AssetBundle\Helper
 */
class CampaignTriggerHelper
{

    /**
     * @param $passthrough
     * @param $event
     *
     * @return bool
     */
    public static function validateAssetDownloadTrigger($passthrough, $event)
    {
        $assetId       = $passthrough->getId();
        $limitToAssets = $event['properties']['assets'];

        if (!empty($limitToAssets) && !in_array($assetId, $limitToAssets)) {
            //no points change
            return false;
        }

        return true;
    }
}