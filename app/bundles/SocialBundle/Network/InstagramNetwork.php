<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class InstagramNetwork extends AbstractNetwork
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIdentifierField()
    {
        return 'instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://api.instagram.com/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    public function getAccessTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @param $response
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_array($response) && isset($response['errors'])) {
            $errors = array();
            foreach ($response->errors as $e) {
                $errors[] = $e['message'] . ' ('.$e['code'].')';
            }

            return implode('; ', $errors);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'clientId'      => 'mautic.social.keyfield.clientid',
            'clientSecret'  => 'mautic.social.keyfield.clientsecret'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array(
            'public_profile',
            'public_activity'
        );
    }
}