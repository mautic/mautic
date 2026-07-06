<?php

namespace Mautic\AssetBundle\Helper;

class PointActionHelper
{
    public static function validateAssetDownload($eventDetails, $action): bool
    {
        $assetId       = $eventDetails->getId();
        $limitToAssets = $action['properties']['assets'];

        // no points change
        return empty($limitToAssets) || in_array($assetId, $limitToAssets);
    }
}
