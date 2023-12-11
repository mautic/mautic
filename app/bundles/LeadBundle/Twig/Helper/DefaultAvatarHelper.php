<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;

final class DefaultAvatarHelper
{
    public function __construct(
        private AssetsHelper $assetsHelper
    ) {
    }

    public function getDefaultAvatar(bool $absolute = false): string
    {
        return $this->assetsHelper->getOverridableUrl('images/avatar.png', $absolute);
    }
}
