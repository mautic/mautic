<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

/**
 * Class FacebookIntegration.
 */
class FacebookIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Facebook';
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return [
            'facebook',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return [
            'share_button',
            'login_button',
            'public_profile',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://www.facebook.com/dialog/oauth';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://graph.facebook.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        // Facebook is inconsistent in that it returns errors as json and data as parameter list
        $values = parent::parseCallbackResponse($data, $postAuthorization);

        if (null === $values) {
            parse_str($data, $values);

            $this->session->set($this->getName().'_tokenResponse', $values);
        }

        return $values;
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        return "https://graph.facebook.com/$endpoint";
    }

    /**
     * Get public data.
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        $this->persistNewLead = false;
        $accessToken          = $this->getContactAccessToken($socialCache);

        if (!isset($accessToken['access_token'])) {
            return;
        }

        $url    = $this->getApiUrl('v2.5/me');
        $fields = array_keys($this->getAvailableLeadFields());

        $parameters = [
            'access_token' => $accessToken['access_token'],
            'fields'       => implode(',', $fields),
        ];

        $data = $this->makeRequest($url, $parameters, 'GET', ['auth_type' => 'rest']);

        if (is_object($data) && isset($data->id)) {
            $info = $this->matchUpData($data);

            if (isset($data->username)) {
                $info['profileHandle'] = $data->username;
            } elseif (isset($data->link)) {
                if (preg_match("/www.facebook.com\/(app_scoped_user_id\/)?(.*?)($|\/)/", $data->link, $matches)) {
                    $info['profileHandle'] = $matches[2];
                }
            } else {
                $info['profileHandle'] = $data->id;
            }
            $info['profileImage'] = "https://graph.facebook.com/{$data->id}/picture?type=large";

            $socialCache['id']          = $data->id;
            $socialCache['profile']     = $info;
            $socialCache['lastRefresh'] = new \DateTime();
            $socialCache['accessToken'] = $this->encryptApiKeys($accessToken);

            $this->getMauticLead($info, $this->persistNewLead, $socialCache, $identifier);

            return $data;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'about'       => ['type' => 'string'],
            'bio'         => ['type' => 'string'],
            'birthday'    => ['type' => 'string'],
            'email'       => ['type' => 'string'],
            'first_name'  => ['type' => 'string'],
            'gender'      => ['type' => 'string'],
            'last_name'   => ['type' => 'string'],
            'link'        => ['type' => 'string'],
            'locale'      => ['type' => 'string'],
            'middle_name' => ['type' => 'string'],
            'name'        => ['type' => 'string'],
            'political'   => ['type' => 'string'],
            'quotes'      => ['type' => 'string'],
            'religion'    => ['type' => 'string'],
            'timezone'    => ['type' => 'string'],
            'website'     => ['type' => 'string'],
        ];
    }
}
