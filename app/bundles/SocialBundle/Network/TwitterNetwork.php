<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class TwitterNetwork extends CommonNetwork
{

    public function getName()
    {
        return 'Twitter';
    }

    public function getOAuthLoginUrl()
    {
        return $this->factory->getRouter()->generate('mautic_social_callback', array('network' => $this->getName()));
    }

    public function getAccessTokenUrl()
    {
        return 'https://api.twitter.com/oauth2/token';
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @return SocialMedia|mixed
     */
    public function oAuthCallback()
    {
        $url      = $this->getAccessTokenUrl();
        $keys     = $this->settings->getApiKeys();

        if (!$url || !isset($keys['clientId']) || !isset($keys['clientSecret'])) {
            return array(false, $this->factory->getTranslator()->trans('mautic.social.missingkeys'));
        }

        $bearer = $this->getBearerToken($keys);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic {$bearer}",
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $data = curl_exec($ch);

        //get the body response
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = substr($data, $header_size);

        curl_close($ch);

        $values = json_decode($body, true);

        //check to see if an entity exists
        $entity = $this->getSettings();
        if ($entity == null) {
            $entity = new SocialNetwork();
            $entity->setName($this->getName());
        }

        if (isset($values['access_token'])) {
            $keys['access_token'] = $values['access_token'];
            $error = false;
        } else {
            $error = $this->parseResponse($values);
        }

        $entity->setApiKeys($keys);

        //save the data
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return array($entity, $error);
    }

    private function getBearerToken($keys)
    {
        //Per Twitter's recommendations
        $consumer_key    = rawurlencode($keys['clientId']);
        $consumer_secret = rawurlencode($keys['clientSecret']);

        return base64_encode($consumer_key . ':' . $consumer_secret);
    }

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

    public function getSupportedFeatures()
    {
        return array(
            'lead_fields',
            'public_activity'
        );
    }

    /**
     * Get public data
     *
     * @param $fields
     * @return array
     */
    public function getUserData($fields)
    {
        $handle = $this->getHandle($fields);

        if ($handle) {
            $url  = "https://api.twitter.com/1.1/users/lookup.json?screen_name={$handle}&include_entities=false";
            $data = $this->makeCall($url);
            if (isset($data[0])) {
                $info                 = $this->matchUpData($data[0]);
                $info['profileUrl']   = "https://twitter.com/{$handle}";
                //remove the size variant
                $image = $data[0]['profile_image_url_https'];
                $image = str_replace(array('_normal', '_bigger', '_mini'), '', $image);
                $info['profileImage'] = $image;
                return $info;
            }
        }
        return null;
    }

    /**
     * Retrieve public posts
     *
     * @param $fields
     * @return array
     */
    public function getPublicActivity($fields)
    {
        $handle = $this->getHandle($fields);
        $tweets = array();
        if ($handle) {
            //due to the way Twitter filters, get more than 10 tweets
            $url  = "https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name={$handle}&exclude_replies=true&count=25&trim_user=true";
            $data = $this->makeCall($url);

            if (!empty($data) && count($data)) {
                foreach ($data as $k => $d) {
                    if ($k == 10) {
                        break;
                    }

                    $tweet = array(
                        'title'       => $d['text'],
                        'url'         => "https://twitter.com/{$handle}/status/{$d['id']}",
                        'published'   => $d['created_at'],
                        'coordinates' => $d['coordinates']
                    );
                    $tweets[] = $tweet;
                }
            }
        }
        return $tweets;
    }

    public function makeCall($url) {
        $request     = $this->factory->getRequest();
        $route       = $request->get('_route');
        $routeParams = $request->get('_route_params');
        $referrer     = $this->factory->getRouter()->generate($route, $routeParams, true);

        $keys = $this->settings->getApiKeys();
        if (empty($keys['access_token'])) {
            return null;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referrer);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$keys['access_token']}",
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
        ));

        $data = curl_exec($ch);

        //get the body response
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = substr($data, $header_size);

        curl_close($ch);

        $values = json_decode($body, true);
        return $values;
    }

    public function getAvailableFields()
    {
        return array(
            "profileUrl"   => array("type" => "string"),
            "profileImage" => array("type" => "string"),
            "name"         => array("type" => "string"),
            "location"     => array("type" => "string"),
            "description"  => array("type" => "string"),
            "url"          => array("type" => "string"),
            "time_zone"     => array("type" => "string"),
            "lang"         => array("type" => "string")
        );
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

            $info[$field] = $values;
        }
        return $info;
    }

    private function getHandle($fields)
    {
        if (isset($fields['twitter'])) {
            //from lead profile
            $handle = $fields['twitter']['value'];
        } elseif (isset($fields['field_twitter'])) {
            //from creating a lead
            $handle = $fields['field_twitter'];
        } else {
            return null;
        }

        if (strpos($handle, 'http') !== false) {
            //extract the handle
            $handle = substr(strrchr(rtrim($handle, '/'), '/'), 1);
        }

        return $handle;
    }
}