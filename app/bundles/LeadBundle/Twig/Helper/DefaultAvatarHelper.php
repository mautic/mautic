<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;

final class DefaultAvatarHelper
{
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    public function __construct(AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    public function getDefaultAvatar(bool $absolute = false): string
    {
        return $this->assetsHelper->getOverridableUrl('images/avatar.png', $absolute);
    }
}
