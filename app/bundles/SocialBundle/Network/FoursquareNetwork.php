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
    private $userEmail = '';
    private $userId    = false;

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
    private function findUserByEmail($email)
    {
        if ($email != $this->userEmail || empty($this->userId)) {
            $email = urlencode($email);
            $keys  = $this->settings->getApiKeys();

            if (!empty($keys['access_token'])) {
                $url  = "https://api.foursquare.com/v2/users/search?v=20140719&email={$email}&oauth_token={$keys['access_token']}";
                $data = $this->makeCall($url);
                if (!empty($data) && isset($data->response->results) && count($data->response->results)) {
                    $result = $data->response->results[0];
                    $this->userId = $result->id;
                    return $this->userId;
                }
            }
        } elseif (!empty($this->userId)) {
            return $this->userId;
        }

        return false;
    }


    /**
     * Get public data
     *
     * @param $fields
     * @return array
     */
    public function getUserData($fields)
    {
        if ($id = $this->getUserId($fields)) {
            $keys = $this->settings->getApiKeys();

            if (!empty($keys['access_token'])) {
                $url  = "https://api.foursquare.com/v2/users/{$id}?v=20140719&&oauth_token={$keys['access_token']}";
                $data = $this->makeCall($url);
                if (!empty($data) && isset($data->response->user)) {
                    $result = $data->response->user;
                    $info   = $this->matchUpData($result);
                    if (isset($result->photo)) {
                        $info['profileImage'] = $result->photo->prefix . '300x300' . $result->photo->suffix;
                    }
                    $info['profileUrl'] = 'https://foursquare.com/user/' . $id;

                    return $info;
                }
            }
        }

        return null;
    }

    public function getErrorsFromResponse($response)
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
            "profileUrl"   => array("type" => "string"),
            "profileImage" => array("type" => "string"),
            "firstName"    => array("type" => "string"),
            "lastName"     => array("type" => "string"),
            "gender"       => array("type" => "string"),
            "homeCity"     => array("type" => "string"),
            "bio"          => array("type" => "string"),
            "contact"      => array(
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

    private function getUserId($fields)
    {
        if (isset($fields['email'])) {
            //from a lead profile
            $email = $fields['email']['value'];
        } elseif (isset($fields['field_email'])) {
            //from creating a lead
            $email = $fields['field_email'];
        } else {

            return null;
        }
        $id = $this->findUserByEmail($email);

        return $id;
    }
}