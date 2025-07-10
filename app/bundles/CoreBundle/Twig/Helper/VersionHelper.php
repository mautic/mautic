<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\AppVersion;

/**
 * final class VersionHelper.
 */
final class VersionHelper
{
    public function __construct(
        private readonly AppVersion $appVersion,
    ) {
    }

    public function getName(): string
    {
        return 'version';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->appVersion->getVersion();
    }
}
