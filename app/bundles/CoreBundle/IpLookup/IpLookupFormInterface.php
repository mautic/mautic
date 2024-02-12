<?php

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
