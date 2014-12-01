<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;
use Mautic\AddonBundle\Integration\AbstractIntegration;

/**
 * Class ZohoIntegration
 */
class ZohoIntegration extends AbstractIntegration
{

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Zoho';
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return 'MauticAddonBundle:Integration:form.html.php';
    }

    /**
     * Get a list of available fields from the connecting API
     *
     * @return array
     */
    public function getAvailableFields()
    {
        return array();
    }

    /**
     * Get a list of keys required to make an API call.  Examples are key, clientId, clientSecret
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array();
    }

    /**
     * Get a list of supported features for this integration
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array();
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, or oauth2
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Get the URL required to obtain an oauth2 access token
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return '';
    }

    /**
     * Get the authentication/login URL for oauth2 access
     *
     * @return string
     */
    protected function getAuthenticationUrl()
    {
        return '';
    }
}
