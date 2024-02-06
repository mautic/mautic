<?php

namespace Mautic\CoreBundle\Helper;

class AppVersion
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return MAUTIC_VERSION;
    }
}
