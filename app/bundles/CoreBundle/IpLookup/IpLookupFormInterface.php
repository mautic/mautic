<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

/**
 * Interface IpLookupFormInterface.
 */
interface IpLookupFormInterface
{
    /**
     * Return name of the form service to append to the Config form UI.
     */
    public function getConfigFormService();

    /**
     * Return array of themes to include in form rendering.
     *
     * @return array
     */
    public function getConfigFormThemes();
}
