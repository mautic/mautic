<?php

namespace MauticPlugin\MauticTestBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class TestIntegration extends AbstractIntegration
{
    /**
     * Returns the name of the social integration that must match the name of the file
     * For example, IcontactIntegration would need Icontact here.
     *
     * @return string
     */
    public function getName()
    {
        return 'Test';
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }
}
