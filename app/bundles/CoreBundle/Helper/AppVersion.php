<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class AppVersion
{
    /**
     * @var string
     */
    private $version;

    public function __construct(\AppKernel $kernel)
    {
        $this->version = $kernel->getVersion();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
