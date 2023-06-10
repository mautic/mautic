<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;

final class DefaultAvatarHelper
{
    public function __construct(private PathsHelper $pathsHelper, private AssetsHelper $assetsHelper)
    {
    }

    public function getDefaultAvatar(bool $absolute = false): string
    {
        $img = $this->pathsHelper->getSystemPath('assets').'/images/avatar.png';

        return $this->assetsHelper->getUrl($img, null, null, $absolute);
    }
}
