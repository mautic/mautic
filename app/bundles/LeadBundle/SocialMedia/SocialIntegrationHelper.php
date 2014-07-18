<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\SocialMedia;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\SocialMedia;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class SocialIntegrationHelper
{
    protected $factory;
    protected $callback;
    protected $entity;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
/*
        $service = $this->getService();
        $this->callback = $this->factory->getRouter()->generate('mautic_social_callback',
            array('client' => $service),
            true //absolute
        );
*/
    }

    /**
     * @param SocialMedia $entity
     */
    public function setEntity(SocialMedia $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get a list of social media helper classes
     *
     * @return array
     */
    public function getSocialIntegrations($service = null)
    {
        static $integrations;

        $available = array(
            'Foursquare',
            'GooglePlus',
            'Twitter'
        );

        if (empty($service)) {
            //get all integrations
            foreach ($available as $a) {
                if (!isset($integrations[$a])) {
                    $class = "\\Mautic\\LeadBundle\\SocialMedia\\$a";
                    $integrations[$a] = new $class($this->factory);
                }
            }
            return $integrations;
        } elseif (in_array($service, $available)) {
            if (!isset($integrations[$service])) {
                $class = "\\Mautic\\LeadBundle\\SocialMedia\\$service";
                $integrations[$service] = new $class($this->factory);
            }
            return $integrations[$service];
        } else {
            throw new MethodNotAllowedHttpException($available);
        }
    }

    /**
     * Get available fields for choices
     *
     * @return mixed
     */
    public function getIntegrationFields($service = null)
    {
        static $fields = array();

        if (empty($fields)) {
            $integrations = $this->getSocialIntegrations();
            $translator   = $this->factory->getTranslator();
            foreach ($integrations as $s => $object) {
                $fields[$s] = array();
                $available  = $object->getAvailableFields();

                foreach ($available as $field => $details) {
                    switch ($details['type']) {
                        case 'string':
                        case 'boolean':
                            $fields[$s][$field] = $translator->trans("mautic.lead.social.{$s}.{$field}");
                            break;
                        case 'object':
                            if (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $name               = $f . ucfirst($field);
                                    $fields[$s][$name] = $translator->trans("mautic.lead.social.{$s}.{$name}");
                                }
                            } else {
                                $fields[$s][$field] = $translator->trans("mautic.lead.social.{$s}.{$field}");
                            }
                            break;
                        case 'array_object':
                            if ($field == "urls" || $field == "url") {
                                //create social profile fields
                                $socialProfileUrls = $this->getSocialProfileUrls();
                                foreach ($socialProfileUrls as $p => $d) {
                                    $fields[$s]["{$p}ProfileUrl"] = $translator->trans("mautic.lead.social.{$s}.{$p}ProfileUrl");
                                }
                                foreach ($details['fields'] as $f) {
                                    $fields[$s]["{$f}Urls"] = $translator->trans("mautic.lead.social.{$s}.{$f}Urls");
                                }
                            } elseif (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $name = $f . ucfirst($field);
                                    $fields[$s][$name] = $translator->trans("mautic.lead.social.{$s}.{$name}");
                                }
                            } else {
                                $fields[$s][$field] = $translator->trans("mautic.lead.social.{$s}.{$field}");
                            }
                            break;
                    }
                }
            }
        }

        return (!empty($service)) ? $fields[$service] : $fields;
    }

    /**
     * Get the SocialMedia repository
     *
     * @return mixed
     */
    public function getRepository()
    {
        return $this->factory->getEntityManager()->getRepository('MauticLeadBundle:SocialMedia');
    }

    /**
     * Get existing tokens stored in the database
     *
     * @return mixed
     */
    public function getTokens()
    {
        $repo = $this->getRepository();
        return $repo->getEntity($this->getService());
    }

    /**
     * Generate the oAuth login url
     */
    public function getOAuthLoginUrl()
    {
        $url  = $this->getAuthenticationUrl();
        $url .= '?client_id='.$this->credentials['id'];
        $url .= '&response_type=code';
        $url .= '&redirect_uri=' . $this->callback;

        return $url;
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @return SocialMedia|mixed
     */
    public function oAuthCallback()
    {
        $request = $this->factory->getRequest();

        $url  = $this->getAccessTokenUrl();
        $url .= '?client_id='.$this->credentials[0];
        $url .= '&client_secret='.$this->credentials[1];
        $url .= '&grant_type=authorization_code';
        $url .= '&redirect_uri=' . $this->callback;
        $url .= '&code='.$request->get('code');

        if (function_exists('curl_init')) {
            $url = "https://www.googleapis.com/plus/v1/people?query=david%40websparkinc.com&key=AIzaSyB3GuBnpj022q0L4kjsYVrLIGZLRObqDpY";
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
        $entity = $this->getTokens();
        if ($entity == null) {
            $entity = new SocialMedia();
            $entity->setService($this->getService());
        }

        if (isset($values['access_token'])) {
            $entity->setAccessToken($values['access_token']);
        }

        if (isset($values['refresh_token'])) {
            $entity->setRefreshToken($values['refresh_token']);
        }

        //save the data
        $this->getRepository()->saveEntity($entity);

        return $entity;
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

    /**
     * Returns popular social media services and URLs
     *
     * @return array
     */
    public function getSocialProfileUrls()
    {
        return array(
            "twitter"   => "twitter.com",
            "facebook"  => array(
                "facebook.com",
                "fb.me"
            ),
            "linkedin"  => "linkedin.com",
            "instagram" => "instagram.com",
            "pinterest" => "pinterest.com",
            "klout"     => "klout.com",
            "youtube"   => array(
                "youtube.com",
                "youtu.be"
            ),
            "flickr"     => "flickr.com"
        );
    }

    public function getAvailableFields()
    {
        return array();
    }

    public function getRequiredKeyFields()
    {
        return array();
    }

}