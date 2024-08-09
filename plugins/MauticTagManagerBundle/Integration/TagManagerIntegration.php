<?php

namespace MauticPlugin\MauticTagManagerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class TagManagerIntegration extends AbstractIntegration
{
    public const PLUGIN_NAME = 'TagManager';

    public function getName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName(): string
    {
        return 'Tag Manager';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     */
    public function getAuthenticationType(): string
    {
        // Just use none for now and I'll build in "basic" later
        return 'none';
    }
}
