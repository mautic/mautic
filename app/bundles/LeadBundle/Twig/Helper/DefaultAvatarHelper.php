<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;

final class DefaultAvatarHelper
{
    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    public function __construct(
        PathsHelper $pathsHelper,
        AssetsHelper $assetsHelper
    ) {
        $this->pathsHelper  = $pathsHelper;
        $this->assetsHelper = $assetsHelper;
    }

    public function getDefaultAvatar(bool $absolute = false): string
    {
        $img = $this->pathsHelper->getSystemPath('assets').'/images/avatar.png';

        return $this->assetsHelper->getUrl($img, null, null, $absolute);
    }
}
