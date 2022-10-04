<?php

namespace MauticPlugin\MauticTagManagerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class TagManagerIntegration extends AbstractIntegration
{
    const PLUGIN_NAME = 'TagManager';

    public function getName()
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName()
    {
        return 'Tag Manager';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        // Just use none for now and I'll build in "basic" later
        return 'none';
    }
}
