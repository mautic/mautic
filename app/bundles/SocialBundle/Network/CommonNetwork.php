<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SocialBundle\Entity\SocialNetwork;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;

class CommonNetwork
{
    protected $factory;
    protected $entity;
    protected $settings;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setSettings(SocialNetwork $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Generate the oAuth login url
     */
    public function getOAuthLoginUrl()
    {
        $keys     = $this->settings->getApiKeys();
        $callback = $this->factory->getRouter()->generate('mautic_social_callback',
            array('network' => $this->getName()),
            true //absolute
        );
        if (isset($keys['clientId']) && isset($keys['clientSecret'])) {
            $url = $this->getAuthenticationUrl();
            $url .= '?client_id=' . $keys['clientId'];
            $url .= '&response_type=code';
            $url .= '&redirect_uri=' . $callback;

            return $url;
        }
        return '#';
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @return SocialMedia|mixed
     */
    public function oAuthCallback()
    {
        $request  = $this->factory->getRequest();
        $url      = $this->getAccessTokenUrl();
        $keys     = $this->settings->getApiKeys();
        $callback = $this->factory->getRouter()->generate('mautic_social_callback',
            array('network' => $this->getName()),
            true //absolute
        );
        if (!$url || !isset($keys['clientId']) || !isset($keys['clientSecret'])) {
            return false;
        }

        $url .= '?client_id='.$keys['clientId'];
        $url .= '&client_secret='.$keys['clientSecret'];
        $url .= '&grant_type=authorization_code';
        $url .= '&redirect_uri=' . $callback;
        $url .= '&code='.$request->get('code');

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $data = @file_get_contents($url);
        }

        $values = json_decode($data, true);

        //check to see if an entity exists
        $entity = $this->getSettings();
        if ($entity == null) {
            $entity = new SocialNetwork();
            $entity->setName($this->getName());
        }

        if (isset($values['access_token'])) {
            $keys['access_token'] = $values['access_token'];

            if (isset($values['refresh_token'])) {
                $keys['refresh_token'] = $values['refresh_token'];
            }
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

    public function makeCall($url) {
        $request     = $this->factory->getRequest();
        $route       = $request->get('_route');
        $routeParams = $request->get('_route_params');
        $referrer     = $this->factory->getRouter()->generate($route, $routeParams, true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referrer);
        $data = @curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data);
        return $data;
    }

    public function getAvailableFields()
    {
        return array();
    }

    public function getRequiredKeyFields()
    {
        return array();
    }

    public function getSupportedFeatures()
    {
        return array();
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getAccessTokenUrl()
    {
        return false;
    }

    public function parseResponse($response)
    {
        return implode(' ', $response);
    }

    public function getPublicActivity($email)
    {
        return array();
    }

}