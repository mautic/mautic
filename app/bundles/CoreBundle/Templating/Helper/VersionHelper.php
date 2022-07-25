<?php

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\AppVersion;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class VersionHelper.
 */
class VersionHelper extends Helper
{
    /**
     * @var AppVersion
     */
    private $appVersion;

    public function __construct(AppVersion $appVersion)
    {
        $this->appVersion = $appVersion;
    }

    /**
     * @return string
     */
    public function getName()
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
