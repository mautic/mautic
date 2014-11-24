<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\SocialBundle\Entity\SocialNetwork;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class NetworkIntegrationHelper
{

    static $factory;

    /**
     * Get a list of social network helper classes
     *
     * @param MauticFactory $factory
     * @param null          $services
     * @param null          $withFeatures
     * @param bool          $alphabetical
     *
     * @return mixed
     */
    public static function getNetworkObjects(MauticFactory $factory, $services = null, $withFeatures = null, $alphabetical = false)
    {
        static $networks;

        static::$factory = $factory;
        $finder = new Finder();
        $finder->files()->name('*Network.php')->in(__DIR__ . '/../Network')->notName('AbstractNetwork.php');
        if ($alphabetical) {
            $finder->sortByName();
        }
        $available = array();
        foreach ($finder as $file) {
            $available[] = substr($file->getBaseName(), 0, -11);
        }

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
            if (empty($alphabetical)) {
                //sort by priority
                uasort($networks, function ($a, $b) {
                    $aP = (int)$a->getPriority();
                    $bP = (int)$b->getPriority();

                    if ($aP === $bP) {
                        return 0;
                    }
                    return ($aP < $bP) ? -1 : 1;
                });
            }
        }

        if (!empty($services)) {
            if (!is_array($services) && isset($networks[$services])) {
                return array($services => $networks[$services]);
            } elseif (is_array($services)) {
                $specific = array();
                foreach ($services as $s) {
                    if (isset($networks[$s])) {
                        $specific[$s] = $networks[$s];
                    }
                }
                return $specific;
            } else {
                throw new MethodNotAllowedHttpException($available);
            }
        } elseif (!empty($withFeatures)) {
            $specific = array();
            foreach ($networks as $n => $d) {
                $settings = $d->getSettings();
                $features = $settings->getSupportedFeatures();

                foreach ($withFeatures as $f) {
                    if (in_array($f, $features)) {
                        $specific[$n] = $d;
                        break;
                    }
                }
            }
            return $specific;
        }

        return $networks;
    }

    /**
     * Get available fields for choices in the config UI
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
                    $fn = $object->matchFieldName($field);
                    switch ($details['type']) {
                        case 'string':
                        case 'boolean':
                            $fields[$s][$fn] = $translator->trans("mautic.social.{$s}.{$fn}");
                            break;
                        case 'object':
                            if (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $fn = $object->matchFieldName($field, $f);
                                    $fields[$s][$fn] = $translator->trans("mautic.social.{$s}.{$fn}");
                                }
                            } else {
                                $fields[$s][$field] = $translator->trans("mautic.social.{$s}.{$fn}");
                            }
                            break;
                        case 'array_object':
                            if ($field == "urls" || $field == "url") {
                                //create social profile fields
                                $socialProfileUrls = self::getSocialProfileUrlRegex();
                                foreach ($socialProfileUrls as $p => $d) {
                                    $fields[$s]["{$p}ProfileHandle"] = $translator->trans("mautic.social.{$s}.{$p}ProfileHandle");
                                }
                                foreach ($details['fields'] as $f) {
                                    $fields[$s]["{$f}Urls"] = $translator->trans("mautic.social.{$s}.{$f}Urls");
                                }
                            } elseif (isset($details['fields'])) {
                                foreach ($details['fields'] as $f) {
                                    $fn = $object->matchFieldName($field, $f);
                                    $fields[$s][$fn] = $translator->trans("mautic.social.{$s}.{$fn}");
                                }
                            } else {
                                $fields[$s][$fn] = $translator->trans("mautic.social.{$s}.{$fn}");
                            }
                            break;
                    }
                }
                uasort($fields[$s], "strnatcmp");
            }
        }

        return (!empty($service)) ? $fields[$service] : $fields;
    }

    /**
     * Returns popular social media services and regex URLs for parsing purposes
     *
     * @param $find     If true, array of regexes to find a handle will be returned;
     *                  If false, array of URLs with a placeholder of %handle% will be returned
     * @return array
     */
    public static function getSocialProfileUrlRegex($find = true)
    {
        if ($find) {
            //regex to find a match
            return array(
                "twitter"   => "/twitter.com\/(.*?)($|\/)/",
                "facebook"  => array(
                    "/facebook.com\/(.*?)($|\/)/",
                    "/fb.me\/(.*?)($|\/)/"
                ),
                "linkedin"  => "/linkedin.com\/in\/(.*?)($|\/)/",
                "instagram" => "/instagram.com\/(.*?)($|\/)/",
                "pinterest" => "/pinterest.com\/(.*?)($|\/)/",
                "klout"     => "/klout.com\/(.*?)($|\/)/",
                "youtube"   => array(
                    "/youtube.com\/user\/(.*?)($|\/)/",
                    "/youtu.be\/user\/(.*?)($|\/)/"
                ),
                "flickr"    => "/flickr.com\/photos\/(.*?)($|\/)/",
                "skype"     => "/skype:(.*?)($|\?)/",
                "google"    => "/plus.google.com\/(.*?)($|\/)/",
            );
        } else {
            //populate placeholder
            return array(
                "twitter"    => "https://twitter.com/%handle%",
                "facebook"   => "https://facebook.com/%handle%",
                "linkedin"   => "https://linkedin.com/in/%handle%",
                "instagram"  => "https://instagram.com/%handle%",
                "pinterest"  => "https://pinterest.com/%handle%",
                "klout"      => "https://klout.com/%handle%",
                "youtube"    => "https://youtube.com/user/%handle%",
                "flickr"     => "https://flickr.com/photos/%handle%",
                "skype"      => "skype:%handle%?call",
                "googleplus" => "https://plus.google.com/%handle%"
            );
        }
    }

    /**
     * Get array of social network entities
     *
     * @return mixed
     */
    public static function getNetworkSettings()
    {
        $repo = static::$factory->getEntityManager()->getRepository('MauticSocialBundle:SocialNetwork');
        return $repo->getNetworkSettings();
    }

    /**
     * Get the user's social profile data from cache or networks if indicated
     *
     * @param $factory
     * @param $lead
     * @param $fields
     * @param $refresh
     * @param $specificNetwork
     * @param $persistLead
     * @param $returnSettings
     *
     * @return array
     */
    public static function getUserProfiles($factory, $lead, $fields = array(), $refresh = false, $specificNetwork = null,
                                           $persistLead = true, $returnSettings = false)
    {
        $socialCache      = $lead->getSocialCache();
        $featureSettings  = array();
        if ($refresh) {
            //regenerate from networks
            $now = new DateTimeHelper();

            //check to see if there are social profiles activated
            $socialNetworks = NetworkIntegrationHelper::getNetworkObjects($factory, $specificNetwork, array('public_profile', 'public_activity'));
            foreach ($socialNetworks as $network => $sn) {
                $settings        = $sn->getSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = self::getUserIdentifierField($sn, $fields);

                if ($returnSettings) {
                    $featureSettings[$network] = $settings->getFeatureSettings();
                }

                if ($identifierField && $settings->isPublished()) {
                    $profile = (!isset($socialCache[$network])) ? array() : $socialCache[$network];

                    //clear the cache
                    unset($profile['profile'], $profile['activity']);

                    if (in_array('public_profile', $features)) {
                        $sn->getUserData($identifierField, $profile);
                    }

                    if (in_array('public_activity', $features)) {
                        $sn->getPublicActivity($identifierField, $profile);
                    }

                    if (!empty($profile['profile']) || !empty($profile['activity'])) {
                        if (!isset($socialCache[$network])) {
                            $socialCache[$network] = array();
                        }

                        $socialCache[$network]['profile']     = (!empty($profile['profile']))  ? $profile['profile'] : array();
                        $socialCache[$network]['activity']    = (!empty($profile['activity'])) ? $profile['activity'] : array();
                        $socialCache[$network]['lastRefresh'] = $now->toUtcString();
                    } else {
                        unset($socialCache[$network]);
                    }
                } elseif (isset($socialCache[$network])) {
                    //network is now not applicable
                    unset($socialCache[$network]);
                }
            }

            if ($persistLead) {
                $lead->setSocialCache($socialCache);
                $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        } elseif ($returnSettings) {
            $socialNetworks = NetworkIntegrationHelper::getNetworkObjects($factory, $specificNetwork, array('public_profile', 'public_activity'));
            foreach ($socialNetworks as $network => $sn) {
                $settings                  = $sn->getSettings();
                $featureSettings[$network] = $settings->getFeatureSettings();
            }
        }

        if ($specificNetwork) {
            return ($returnSettings) ? array(array($specificNetwork => $socialCache[$specificNetwork]), $featureSettings)
                : array($specificNetwork => $socialCache[$specificNetwork]);
        } else {
            return ($returnSettings) ? array($socialCache, $featureSettings) : $socialCache;
        }
    }

    /**
     * @param      $factory
     * @param      $lead
     * @param bool $network
     */
    public static function clearNetworkCache($factory, $lead, $network = false)
    {
        $socialCache = $lead->getSocialCache();
        if (!empty($network)) {
            unset($socialCache[$network]);
        } else {
            $socialCache = array();
        }
        $lead->setSocialCache($socialCache);
        $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
        return $socialCache;
    }

    /**
     * Gets an array of the HTML for share buttons
     *
     * @param $factory
     */
    public static function getShareButtons($factory)
    {
        static $shareBtns = array();

        if (empty($shareBtns)) {
            $socialNetworks = NetworkIntegrationHelper::getNetworkObjects($factory, null, array('share_button'), true);
            $templating     = $factory->getTemplating();
            foreach ($socialNetworks as $network => $details) {
                $settings        = $details->getSettings();
                $featureSettings = $settings->getFeatureSettings();
                $apiKeys         = $settings->getApiKeys();
                $shareSettings   = isset($featureSettings['shareButton']) ? $featureSettings['shareButton'] : array();

                //add the api keys for use within the share buttons
                $shareSettings['keys'] = $apiKeys;
                $shareBtns[$network]   = $templating->render("MauticSocialBundle:Network/$network:share.html.php", array(
                    'settings' => $shareSettings,
                ));
            }
        }
        return $shareBtns;
    }

    /**
     * Loops through field values available and finds the field the network needs to obtain the user
     *
     * @param $networkObject
     * @param $fields
     * @return bool
     */
    public static function getUserIdentifierField($networkObject, $fields)
    {
        $identifierField = $networkObject->getIdentifierFields();
        $identifier      = (is_array($identifierField)) ? array() : false;
        $matchFound      = false;

        $findMatch = function ($f, $fields) use(&$identifierField, &$identifier, &$matchFound) {
            if (is_array($identifier)) {
                //there are multiple fields the network can identify by
                foreach ($identifierField as $idf) {
                    $value = (is_array($fields[$f]) && isset($fields[$f]['value'])) ? $fields[$f]['value'] : $fields[$f];

                    if (!in_array($value, $identifier) && strpos($f, $idf) !== false) {
                        $identifier[$f] = $value;
                        if (count($identifier) === count($identifierField)) {
                            //found enough matches so break
                            $matchFound = true;
                            break;
                        }
                    }
                }
            } elseif ($identifierField == $f || strpos($f, $identifierField) !== false) {
                $matchFound = true;
                $identifier = (is_array($fields[$f])) ? $fields[$f]['value'] : $fields[$f];
            }
        };

        if (isset($fields['core'])) {
            //fields are group
            foreach ($fields as $group => $groupFields) {
                $availableFields = array_keys($groupFields);
                foreach ($availableFields as $f) {
                    $findMatch($f, $groupFields);

                    if ($matchFound) {
                        break;
                    }
                }
            }
        } else {
            $availableFields = array_keys($fields);
            foreach ($availableFields as $f) {
                $findMatch($f, $fields);

                if ($matchFound) {
                    break;
                }
            }
        }

        return $identifier;
    }
}