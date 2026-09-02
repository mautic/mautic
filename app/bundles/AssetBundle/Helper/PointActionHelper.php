<?php

namespace Mautic\AssetBundle\Helper;

use Mautic\AssetBundle\Entity\Asset;

final class PointActionHelper
{
    /**
     * @param array<string, mixed> $action
     */
    public static function validateAssetDownload(Asset $eventDetails, array $action): bool
    {
        $assetId       = $eventDetails->getId();
        $limitToAssets = $action['properties']['assets'];

        // no points change
        return empty($limitToAssets) || in_array($assetId, $limitToAssets);
    }
}
