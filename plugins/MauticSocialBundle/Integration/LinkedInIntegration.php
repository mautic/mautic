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
 * Class LinkedInIntegration
 */
class LinkedInIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'LinkedIn';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array(
            'share_button',
            'login_button'
        );
    }

    public function getAuthScope()
    {
        return 'r_emailaddress';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://www.linkedin.com/uas/oauth2/authorization';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://www.linkedin.com/uas/oauth2/accessToken';
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        return "https://api.linkedin.com/$endpoint";
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return array(
            'linkedin'
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return array(
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret'
        );
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
        //tell getUserId to return a user array if it obtains it
        $socialCache  = array();
        $access_token = $this->factory->getSession()->get($this->getName().'_tokenResponse');

        $fields        = $this->getFieldNames($this->getAvailableLeadFields());
        $profileFields = implode(",", array_keys($fields));

        if ($identifier[$this->getName()] === null) {
            $identifier[$this->getName()] = "v1/people/~:(id,picture-url,".$profileFields.")";
        }

        if(isset($access_token['access_token'])) {
            $identifier['access_token'] = $access_token['access_token'];

            $this->preventDoubleCall = true;

            if ($id = $this->getUserId($identifier, $socialCache)) {

                if (is_object($id)) {
                    //getUserId has already obtained the data
                    $data = $id;
                } else {
                    $url  = $this->getApiUrl("$id");
                    $data = $this->makeRequest($url, array(), 'GET', array('auth_type' => 'rest'));
                }

                $info = $this->matchUpData($data);

                if (isset($data->publicProfileUrl)) {
                    $info['profileHandle'] = str_replace("https://www.linkedin.com/", '', $data->publicProfileUrl);
                }

                $info['profileImage'] = preg_replace('/\?.*/', '', $data->pictureUrl);


                $socialCache['profile']     = $info;
                $socialCache['lastRefresh'] = new \DateTime();
                $socialCache['accessToken'] = $this->encryptApiKeys($access_token);

                $this->getMauticLead($info, true, $socialCache, $identifier);

                return $data;

                //$this->preventDoubleCall = false;
            }
        }
        return null;
    }

    /**
     * gets the name of field to sent to linked in API to fetch social profile data
     *
     * @return array()
     */
    protected function getFieldNames($leadFields)
    {
        $fields = array();
        foreach ($leadFields as $leadField) {
            $fields[$leadField['fieldName']] = $leadField['fieldName'];
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanIdentifier($identifier)
    {
        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $identifiers = $this->cleanIdentifier($identifier);

        if (!isset($identifier['access_token'])) {
            return;
        }

        if (isset($identifiers['LinkedIn'])) {
            $url = $this->getApiUrl($identifiers["LinkedIn"]);

            if (isset($identifier['access_token'])) {
                $parameters['oauth2_access_token'] = $identifier['access_token'];
            }
            $parameters['format'] = "json";
            $data                 = $this->makeRequest($url, $parameters, 'GET', array('auth_type' => 'rest'));

            if ($data && isset($data->id)) {
                $socialCache['id'] = $data->id;

                //return the entire data set if the function has been called from getUserData()
                return ($this->preventDoubleCall) ? $data : $socialCache['id'];
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = array())
    {
        // Until lead profile support is restored
        //return array();

        return array(
            'firstName'        => array('type' => 'string', 'fieldName' => 'first-name'),
            'lastName'         => array('type' => 'string', 'fieldName' => 'last-name'),
            'maidenName'       => array('type' => 'string', 'fieldName' => 'maiden-name'),
            'formattedName'    => array('type' => 'string', 'fieldName' => 'formatted-name'),
            'headline'         => array('type' => 'string', 'fieldName' => 'headline'),
            'location'         => array(
                'type'      => 'object',
                'fields'    => array(
                    'name'
                ),
                'fieldName' => 'location'
            ),
            'summary'          => array('type' => 'string', 'fieldName' => 'summary'),
            'specialties'      => array('type' => 'string', 'fieldName' => 'specialties'),
            'positions'        => array(
                'type'      => 'array_object',
                'fields'    => array(
                    'values'
                ),
                'fieldName' => 'positions'
            ),
            'publicProfileUrl' => array('type' => 'string', 'fieldName' => 'public-profile-url'),
            'emailAddress'     => array('type' => 'string', 'fieldName' => 'email-address')
        );
    }
}
