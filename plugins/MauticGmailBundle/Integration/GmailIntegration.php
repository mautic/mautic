<?php

namespace MauticPlugin\MauticGmailBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class GmailIntegration extends AbstractIntegration
{
    public function getName(): string
    {
        return 'Gmail';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     */
    public function getAuthenticationType(): string
    {
        // Just use none for now and I'll build in "basic" later
        return 'none';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'secret' => 'mautic.integration.gmail.secret',
        ];
    }
}
