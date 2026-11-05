<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class AppVersion
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return MAUTIC_VERSION;
    }
}
