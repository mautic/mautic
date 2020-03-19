<?php

namespace Mautic\LeadBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;

class DefaultAvatarHelper
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

    public function getDefaultAvatar(): string
    {
        $img = $this->pathsHelper->getSystemPath('assets', false).'/images/avatar.png';

        return UrlHelper::rel2abs($this->assetsHelper->getUrl($img));
    }
}
