<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\SocialBundle\Entity\SocialNetwork;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class NetworkIntegrationHelper
{

    static $factory;

    /**
     * Get a list of social media helper classes
     *
     * @return array
     */
    public static function getNetworkObjects(MauticFactory $factory, $service = null)
    {
        static $networks;

        static::$factory = $factory;
        $available = array(
            'Foursquare',
            'GooglePlus',
            'Twitter'
        );

        if (empty($networks)) {
            $networkSettings = self::getNetworkSettings();
            //get all integrations
            foreach ($available as $a) {
                if (!isset($integrations[$a])) {
                    $class = "\\Mautic\\SocialBundle\\Network\\{$a}Network";
                    $networks[$a] = new $class($factory);
                    if (!isset($networkSettings[$a])) {
                        $networkSettings[$a] = new SocialNetwork();
                        $networkSettings[$a]->setName($a);
                    }
                    $networks[$a]->setSettings($networkSettings[$a]);
                }
            }
        }

        if (!empty($service)) {
            if (isset($networks[$service])) {
                return $networks[$service];
            } else {
                throw new MethodNotAllowedHttpException($available);
            }
        }
        return $networks;
    }


    /**
     * Get available fields for choices
     *
     * @return mixed
     */
    public static function getAvailableFields(MauticFactory $factory, $service = null)
    {
        static $fields = array();

        if (empty($fields)) {
            $integrations = self::getNetworkObjects($factory);
            $translator   = $factory->getTranslator();
            foreach ($integrations as $s => $object) {
                $fields[$s] = array();
                $available  = $object->getAvailableFields();

                foreach ($available as $field => $details) {
                    switch ($details['type']) {
                        case 'string':
                        case 'boolean':
                            $fields[$s][$field] = $translator->trans("mautic.social.{$s}.{$field}");
                            break;
                        case 'object':
                            if (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $name               = $f . ucfirst($field);
                                    $fields[$s][$name] = $translator->trans("mautic.social.{$s}.{$name}");
                                }
                            } else {
                                $fields[$s][$field] = $translator->trans("mautic.social.{$s}.{$field}");
                            }
                            break;
                        case 'array_object':
                            if ($field == "urls" || $field == "url") {
                                //create social profile fields
                                $socialProfileUrls = self::getSocialProfileUrls();
                                foreach ($socialProfileUrls as $p => $d) {
                                    $fields[$s]["{$p}ProfileUrl"] = $translator->trans("mautic.social.{$s}.{$p}ProfileUrl");
                                }
                                foreach ($details['fields'] as $f) {
                                    $fields[$s]["{$f}Urls"] = $translator->trans("mautic.social.{$s}.{$f}Urls");
                                }
                            } elseif (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $name = $f . ucfirst($field);
                                    $fields[$s][$name] = $translator->trans("mautic.social.{$s}.{$name}");
                                }
                            } else {
                                $fields[$s][$field] = $translator->trans("mautic.social.{$s}.{$field}");
                            }
                            break;
                    }
                }
            }
        }

        return (!empty($service)) ? $fields[$service] : $fields;
    }


    /**
     * Returns popular social media services and URLs
     *
     * @return array
     */
    public static function getSocialProfileUrls()
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

    /**
     * @return mixed
     */
    public static function getNetworkSettings()
    {
        $repo = static::$factory->getEntityManager()->getRepository('MauticSocialBundle:SocialNetwork');
        return $repo->getNetworkSettings();
    }
}