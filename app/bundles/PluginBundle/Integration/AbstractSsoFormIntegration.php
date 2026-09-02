<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Integration;

/**
 * Used by SSO auth plugins that use credentials from the login form to authenticate.
 */
abstract class AbstractSsoFormIntegration extends AbstractSsoServiceIntegration
{
    /**
     * @return string[]
     */
    public function getSupportedFeatures(): array
    {
        return [
            'sso_form',
        ];
    }

    /**
     * Get form settings; authorization is not needed since it is done when a user logs in.
     *
     * @return array<string, mixed>
     */
    public function getFormSettings(): array
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }
}
