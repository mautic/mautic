<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class FacebookIntegration
 */
class FacebookIntegration extends SocialIntegration
{
    /**
     * Used in getUserData to prevent a double user search call with getUserId
     *
     * @var bool
     */
    private $preventDoubleCall = false;

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
            'login_button'
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
        if ($postAuthorization) {
            parse_str($data, $values);
            $this->factory->getSession()->set($this->getName().'_tokenResponse', $values);
            return $values;
        } else {
            return parent::parseCallbackResponse($data, $postAuthorization);
        }
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
        //tell getUserId to return a user array if it obtains it

        $access_token = $this->factory->getSession()->get($this->getName().'_tokenResponse');

        if($identifier[$this->getName()]===null){
            $identifier[$this->getName()] = "v2.5/me";
        }


        $identifier['access_token'] =$access_token['access_token'];

        $this->preventDoubleCall = true;

        if ($id = $this->getUserId($identifier, $socialCache)) {

            if (is_object($id)) {
                //getUserId has already obtained the data
                $data = $id;
            } else {
                $url = $this->getApiUrl("$id");

                $data = $this->makeRequest($url, array(), 'GET', array('auth_type' => 'rest'));

            }

            $info = $this->matchUpData($data);

            if (isset($data->username)) {
                $info['profileHandle'] = $data->username;
            } elseif (isset($data->link)) {
                $info['profileHandle'] = str_replace('https://www.facebook.com/', '', $data->link);
            } else {
                $info['profileHandle'] = $data->id;
            }

            $info['profileImage'] = "https://graph.facebook.com/{$data->id}/picture?type=large";


            $socialCache['profile'] = $info;
            $socialCache['lastRefresh'] = new \DateTime();
            $socialCache['accessToken'] = $this->encryptApiKeys($access_token);

            $this->getMauticLead($info, true, $socialCache, $identifier);

            $this->preventDoubleCall = false;

            return $data;
        }
    }

    /**$post
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
        $access_token = $this->factory->getSession()->get($this->getName().'_tokenResponse');

        if(!isset($access_token['access_token'])){
            return;
        }

        if (isset($identifiers['Facebook'])) {
            $url = $this->getApiUrl($identifiers["Facebook"]);

            if(isset($identifier['access_token'])){
                $parameters['access_token']=$identifier['access_token'];
            }

            $fields = array_keys($this->getAvailableLeadFields());
            $parameters['fields'] = implode(",",$fields);

            $data = $this->makeRequest($url, $parameters, 'GET', array('auth_type' => 'rest'));

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
            'first_name' => array('type' => 'string'),
            'last_name'  => array('type' => 'string'),
            'name'       => array('type' => 'string'),
            'gender'     => array('type' => 'string'),
            'locale'     => array('type' => 'string'),
            'email'      => array('type' => 'string'),
            'link'       => array('type' => 'string'),
        );
    }
}
