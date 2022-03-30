<?php

namespace Mautic\PluginBundle\Integration;

/**
 * Used by SSO auth plugins that use credentials from the login form to authenticate.
 *
 * Class AbstractSsoFormIntegration
 */
abstract class AbstractSsoFormIntegration extends AbstractSsoServiceIntegration
{
    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [
            'sso_form',
        ];
    }

    /**
     * Get form settings; authorization is not needed since it is done when a user logs in.
     *
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }
}
