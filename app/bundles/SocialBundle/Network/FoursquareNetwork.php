<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class FoursquareNetwork extends CommonNetwork
{

    public function getName()
    {
        return 'Foursquare';
    }

    public function getAuthenticationUrl()
    {
        return 'https://foursquare.com/oauth2/authenticate';
    }

    public function getAccessTokenUrl()
    {
        return 'https://foursquare.com/oauth2/access_token';
    }

    public function getRequiredKeyFields()
    {
        return array(
            'clientId'      => 'mautic.social.keyfield.clientid',
            'clientSecret'  => 'mautic.social.keyfield.clientsecret'
        );
    }

    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * @param $email
     * @return mixed|null
     */
    public function findUserByEmail($email)
    {
        $email = urlencode($email);
        $keys  = $this->settings->getApiKeys();

        if (!empty($keys['access_token'])) {
            $url  = "https://api.foursquare.com/v2/users/search?v=20140719&email={$email}&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);
            if (!empty($data) && isset($data->response->results) && count($data->response->results)) {
                $result = $data->response->results[0];
                return $result;
            }
        }

        return null;
    }


    /**
     * Get public data
     *
     * @param $email
     * @return array
     */
    public function getUserData($email)
    {
        $data = $this->findUserByEmail($email);

        if ($data) {
            $keys = $this->settings->getApiKeys();

            if (!empty($keys['access_token'])) {
                $url  = "https://api.foursquare.com/v2/users/{$data->id}?v=20140719&&oauth_token={$keys['access_token']}";
                $data = $this->makeCall($url);
                if (!empty($data) && isset($data->response->user)) {
                    $result = $data->response->user;
                    $info   = $this->matchUpData($result);
                    if (isset($result->photo)) {
                        $info['FoursquareImage'] = $result->photo->prefix . '100x100' . $result->photo->suffix;
                    }
                    return $info;
                }
            }
        }
    }

    public function parseResponse($response)
    {
        if (is_object($response) && isset($response->meta->errorDetail)) {
            return $response->meta->errorDetail . ' (' . $response->meta->code . ')';
        }
        return '';
    }


    /**
     * Convert and assign the data to assignable fields
     *
     * @param $data
     */
    protected function matchUpData($data)
    {
        $info       = array();
        $available  = $this->getAvailableFields();

        foreach ($data as $field => $values) {
            if (!isset($available[$field]))
                continue;

            $fieldDetails = $available[$field];

            switch ($fieldDetails['type']) {
                case 'string':
                case 'boolean':
                    $info[$field] = $values;
                    break;
                case 'object':
                    foreach ($fieldDetails['fields'] as $f) {
                        if (isset($values->$f)) {
                            $name        = $f . ucfirst($field);
                            $info[$name] = $values->$f;
                        }
                    }
                    break;
            }
        }
        return $info;
    }

    public function getAvailableFields()
    {
        return array(
            "firstName" => array("type" => "string"),
            "lastName"  => array("type" => "string"),
            "gender"    => array("type" => "string"),
            "homeCity"  => array("type" => "string"),
            "bio"       => array("type" => "string"),
            "contact"   => array(
                "type"   => "object",
                "fields" => array(
                    "twitter",
                    "facebook",
                    "phone"
                )
            )
        );
    }

    public function getSupportedFeatures()
    {
        return array(
            'lead_fields',
            'public_activity'
        );
    }
}