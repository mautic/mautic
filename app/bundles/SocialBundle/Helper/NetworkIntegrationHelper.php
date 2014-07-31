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
     * @param null          $service
     * @param null          $withFeatures
     * @param bool          $alphabetical
     *
     * @return mixed
     */
    public static function getNetworkObjects(MauticFactory $factory, $service = null, $withFeatures = null, $alphabetical = false)
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

        if (!empty($service)) {
            if (isset($networks[$service])) {
                return $networks[$service];
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
                                $socialProfileUrls = self::getSocialProfileUrlRegex();
                                foreach ($socialProfileUrls as $p => $d) {
                                    $fields[$s]["{$p}ProfileHandle"] = $translator->trans("mautic.social.{$s}.{$p}ProfileHandle");
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
                asort($fields[$s], SORT_NATURAL);
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
                "skype"     => "/skype:(.*?)($|\?)/"
            );
        } else {
            //populate placeholder
            return array(
                "twitter"   => "https://twitter.com/%handle%",
                "facebook"  => "https://facebook.com/%handle%",
                "linkedin"  => "https://linkedin.com/in/%handle%",
                "instagram" => "https://instagram.com/%handle%",
                "pinterest" => "https://pinterest.com/%handle%",
                "klout"     => "https://klout.com/%handle%",
                "youtube"   => "https://youtube.com/user/%handle%",
                "flickr"    => "https://flickr.com/photos/%handle%",
                "skype"     => "skype:%handle%?call"
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
     * @param $persistLead
     * @param $includeLeadFields
     *
     * @return array
     */
    public static function getUserProfiles($factory, $lead, $fields, $refresh = true, $persistLead = true,
                                           $includeLeadFields = false)
    {
        $socialCache  = $lead->getSocialCache();
        $leadFields   = array();

        if ($refresh) {
            //regenerate from networks

            //check to see if there are social profiles activated
            $socialNetworks = NetworkIntegrationHelper::getNetworkObjects($factory, null, array('public_profile', 'public_activity'));

            foreach ($socialNetworks as $network => $sn) {
                $settings        = $sn->getSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = self::getUserIdentifierField($sn, $fields);

                if ($identifierField && $settings->isPublished()) {
                    if (!isset($socialCache[$network])) {
                        $socialCache[$network] = array();
                    }

                    if ($includeLeadFields) {
                        $leadFields[$network] = $settings->getLeadFields();
                    }

                    if (!isset($socialCache[$network]['profile'])) {
                        $socialCache[$network]['profile'] = array();
                    }
                    if (in_array('public_profile', $features)) {
                        $sn->getUserData($identifierField, $socialCache[$network]);

                    }

                    if (!isset($socialCache[$network]['activity'])) {
                        $socialCache[$network]['activity'] = array();
                    }
                    if (in_array('public_activity', $features)) {
                        $sn->getPublicActivity($identifierField, $socialCache[$network]);
                    }

                    //regenerating all of the cache so remove update notice
                    if (isset($socialCache[$network]['updated'])) {
                        $now = new DateTimeHelper();
                        $socialCache[$network]['lastRefresh'] = $now->toUtcString();
                        unset($socialCache[$network]['updated']);
                    }
                }
            }

            if ($persistLead) {
                $lead->setSocialCache($socialCache);
                $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        } elseif ($includeLeadFields) {
            $socialNetworks = NetworkIntegrationHelper::getNetworkObjects($factory, null, array('public_profile', 'public_activity'));
            foreach ($socialNetworks as $network => $sn) {
                $settings             = $sn->getSettings();
                $leadFields[$network] = $settings->getLeadFields();
            }
        }

        return ($includeLeadFields) ? array($socialCache, $leadFields) : $socialCache;
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
        $identifierField = $networkObject->getIdentifierField();
        $identifier      = (is_array($identifierField)) ? array() : false;
        $matchFound      = false;

        $findMatch = function ($f, $fields) use(&$identifierField, &$identifier, &$matchFound) {
            if (is_array($identifier)) {
                //there are multiple fields the network can identify by
                foreach ($identifierField as $idf) {
                    $value = (is_array($fields[$f]) && isset($fields[$f]['value'])) ? $fields[$f]['value'] : $fields[$f];

                    if (!in_array($value, $identifier) && strpos($f, $idf) !== false) {
                        //remove field prefix in case this is from a lead form so the network doesn't have to account for it
                        $parsedField              = str_ireplace('field_', '', $f);
                        $identifier[$parsedField] = $value;
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