<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Provider;

interface VersionProviderInterface
{
    /**
     * @return string
     */
    public function fetchVersion();

    /**
     * @return bool
     */
    public function isMariaDb();

    /**
     * @return bool
     */
    public function isMySql();
}
