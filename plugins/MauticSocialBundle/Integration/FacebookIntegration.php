<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

/**
 * Class FacebookIntegration
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
        return array(
            'facebook'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array(
            'share_button',
            'login_button',
            'public_profile'
        );
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

            $this->factory->getSession()->set($this->getName().'_tokenResponse', $values);
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
     * Get public data
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        $persistLead = false;
        $accessToken = $this->getAccessToken($socialCache);

        if (!isset($accessToken['access_token'])) {

            return;
        } elseif (isset($accessToken['persist_lead'])) {
            $persistLead = $accessToken['persist_lead'];
            unset($accessToken['persist_lead']);
        }

        $url    = $this->getApiUrl("v2.5/me");
        $fields = array_keys($this->getAvailableLeadFields());

        $parameters = array(
            'access_token' => $accessToken['access_token'],
            'fields'       => implode(",",$fields)
        );

        $data = $this->makeRequest($url, $parameters, 'GET', array('auth_type' => 'rest'));

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

            $this->getMauticLead($info, $persistLead, $socialCache, $identifier);

            return $data;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = array())
    {
        return array(
            'about'       => array('type' => 'string'),
            'bio'         => array('type' => 'string'),
            'birthday'    => array('type' => 'string'),
            'email'       => array('type' => 'string'),
            'first_name'  => array('type' => 'string'),
            'gender'      => array('type' => 'string'),
            'last_name'   => array('type' => 'string'),
            'link'        => array('type' => 'string'),
            'locale'      => array('type' => 'string'),
            'middle_name' => array('type' => 'string'),
            'name'        => array('type' => 'string'),
            'political'   => array('type' => 'string'),
            'quotes'      => array('type' => 'string'),
            'religion'    => array('type' => 'string'),
            'timezone'    => array('type' => 'string'),
            'website'     => array('type' => 'string')
        );
    }
}
