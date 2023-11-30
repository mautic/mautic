<?php

namespace Mautic\AssetBundle\Helper;

class PointActionHelper
{
    public static function validateAssetDownload($eventDetails, $action): bool
    {
        $assetId       = $eventDetails->getId();
        $limitToAssets = $action['properties']['assets'];

        if (!empty($limitToAssets) && !in_array($assetId, $limitToAssets)) {
            // no points change
            return false;
        }

        return true;
    }
}
