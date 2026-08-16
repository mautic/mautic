<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\IpLookup;

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
