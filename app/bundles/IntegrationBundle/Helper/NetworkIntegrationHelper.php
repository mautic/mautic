<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\SocialBundle\Entity\SocialNetwork;
use Mautic\SocialBundle\Network\AbstractNetwork;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class NetworkIntegrationHelper
 */
class NetworkIntegrationHelper
{

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get a list of network helper classes
     *
     * @param array|string $services
     * @param array        $withFeatures
     * @param bool         $alphabetical
     *
     * @return mixed
     */
    public function getNetworkObjects($services = null, $withFeatures = null, $alphabetical = false)
    {
        static $networks, $available;

        if (empty($networks)) {
            // We need to get the core bundles so we can get our lookup path for the core network classes
            $bundles = $this->factory->getParameter('bundles');

            // And we'll be scanning the addon bundles for additional classes, so have that data on standby
            $addons  = $this->factory->getParameter('addon.bundles');

            // Quickly figure out which addons are enabled so we only process those
            /** @var \Mautic\IntegrationBundle\Entity\IntegrationRepository $integrationRepo */
            $integrationRepo = $this->factory->getEntityManager()->getRepository('MauticIntegrationBundle:Integration');
            $addonStatuses = $integrationRepo->getBundleStatus();

            foreach ($addons as $addon) {
                if (!$addonStatuses[$addon['bundle']]) {
                    unset($addons[$addon['base']]);
                }
            }

            // Scan the SocialBundle for our core network classes
            $finder = new Finder();
            $finder->files()->name('*Network.php')->in($bundles['Social']['directory'] . '/Network')->notName('AbstractNetwork.php');
            if ($alphabetical) {
                $finder->sortByName();
            }
            $available = array('core' => array(), 'addon' => array());
            foreach ($finder as $file) {
                $available['core'][] = substr($file->getBaseName(), 0, -11);
            }

            // Scan the addons for network classes
            foreach ($addons as $addon) {
                if (is_dir($addon['directory'] . '/Network')) {
                    $finder = new Finder();
                    $finder->files()->name('*Network.php')->in($addon['directory'] . '/Network');

                    if ($alphabetical) {
                        $finder->sortByName();
                    }

                    foreach ($finder as $file) {
                        $available['addon'][] = array(
                            'network' => substr($file->getBaseName(), 0, -11),
                            'namespace' => str_replace('Mautic', '', $addon['bundle'])
                        );
                    }
                }
            }

            $networkSettings = $this->getNetworkSettings();

            // Get all core integrations
            foreach ($available['core'] as $a) {
                if (!isset($integrations[$a])) {
                    $class = "\\Mautic\\SocialBundle\\Network\\{$a}Network";
                    $networks[$a] = new $class($this->factory);
                    $networks[$a]->setIsCore(true);
                    if (!isset($networkSettings[$a])) {
                        $networkSettings[$a] = new SocialNetwork();
                        $networkSettings[$a]->setName($a);
                    }
                    $networks[$a]->setSettings($networkSettings[$a]);
                }
            }

            // Get all the addon integrations
            foreach ($available['addon'] as $a) {
                if (!isset($integrations[$a['network']])) {
                    $class = "\\MauticAddon\\{$a['namespace']}\\Network\\{$a['network']}Network";
                    $networks[$a['network']] = new $class($this->factory);
                    $networks[$a['network']]->setIsCore(false);
                    if (!isset($networkSettings[$a['network']])) {
                        $networkSettings[$a['network']] = new SocialNetwork();
                        $networkSettings[$a['network']]->setName($a['network']);
                    }
                    $networks[$a['network']]->setSettings($networkSettings[$a['network']]);
                }
            }

            if (empty($alphabetical)) {
                // Sort by priority
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
     * @param string|null $service
     *
     * @return mixed
     */
    public function getAvailableFields($service = null)
    {
        static $fields = array();

        if (empty($fields)) {
            $integrations = $this->getNetworkObjects();
            $translator   = $this->factory->getTranslator();
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
                                $socialProfileUrls = $this->getSocialProfileUrlRegex();
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
     * @param bool $find If true, array of regexes to find a handle will be returned;
     *                   If false, array of URLs with a placeholder of %handle% will be returned
     *
     * @return array
     * @todo Extend this method to allow addons to add URLs to these arrays
     */
    public function getSocialProfileUrlRegex($find = true)
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
    public function getNetworkSettings()
    {
        return $this->factory->getEntityManager()->getRepository('MauticSocialBundle:SocialNetwork')->getNetworkSettings();
    }

    /**
     * Get the user's social profile data from cache or networks if indicated
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $fields
     * @param bool                           $refresh
     * @param string                         $specificNetwork
     * @param bool                           $persistLead
     * @param bool                           $returnSettings
     *
     * @return array
     */
    public function getUserProfiles($lead, $fields = array(), $refresh = false, $specificNetwork = null, $persistLead = true, $returnSettings = false)
    {
        $socialCache      = $lead->getSocialCache();
        $featureSettings  = array();
        if ($refresh) {
            //regenerate from networks
            $now = new DateTimeHelper();

            //check to see if there are social profiles activated
            $socialNetworks = $this->getNetworkObjects($specificNetwork, array('public_profile', 'public_activity'));
            foreach ($socialNetworks as $network => $sn) {
                $settings        = $sn->getSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = $this->getUserIdentifierField($sn, $fields);

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
                $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        } elseif ($returnSettings) {
            $socialNetworks = $this->getNetworkObjects($specificNetwork, array('public_profile', 'public_activity'));
            foreach ($socialNetworks as $network => $sn) {
                $settings                  = $sn->getSettings();
                $featureSettings[$network] = $settings->getFeatureSettings();
            }
        }

        if ($specificNetwork) {
            return ($returnSettings) ? array(array($specificNetwork => $socialCache[$specificNetwork]), $featureSettings)
                : array($specificNetwork => $socialCache[$specificNetwork]);
        }

        return ($returnSettings) ? array($socialCache, $featureSettings) : $socialCache;
    }

    /**
     * @param      $lead
     * @param bool $network
     */
    public function clearNetworkCache($lead, $network = false)
    {
        $socialCache = $lead->getSocialCache();
        if (!empty($network)) {
            unset($socialCache[$network]);
        } else {
            $socialCache = array();
        }
        $lead->setSocialCache($socialCache);
        $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
        return $socialCache;
    }

    /**
     * Gets an array of the HTML for share buttons
     */
    public function getShareButtons()
    {
        static $shareBtns = array();

        if (empty($shareBtns)) {
            $socialNetworks = $this->getNetworkObjects(null, array('share_button'), true);
            $templating     = $this->factory->getTemplating();
            foreach ($socialNetworks as $network => $details) {
                $settings        = $details->getSettings();
                $featureSettings = $settings->getFeatureSettings();
                $apiKeys         = $settings->getApiKeys();
                $shareSettings   = isset($featureSettings['shareButton']) ? $featureSettings['shareButton'] : array();

                //add the api keys for use within the share buttons
                // TODO - The template path needs to be extended to support addons
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
    public function getUserIdentifierField($networkObject, $fields)
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

    /**
     * Get the path to the network's icon relative to the site root
     *
     * @param AbstractNetwork $network
     *
     * @return string
     */
    public function getIconPath(AbstractNetwork $network)
    {
        $systemPath  = $this->factory->getSystemPath('root');
        $genericIcon = 'app/bundles/SocialBundle/Assets/img/generic.jpg';
        $name        = $network->getSettings()->getName();

        if ($network->getIsCore()) {
            $icon = 'app/bundles/SocialBundle/Assets/img/' . strtolower($name) . '.jpg';

            if (file_exists($systemPath . '/' . $icon)) {
                return $icon;
            }

            return $genericIcon;
        }

        // For non-core bundles, we need to extract out the bundle's name to figure out where in the filesystem to look for the icon
        $className = get_class($network);
        $exploded  = explode('\\', $className);
        $icon      = 'addons/' . $exploded[1] . '/Assets/img/' . strtolower($name) . '.jpg';

        if (file_exists($systemPath . '/' . $icon)) {
            return $icon;
        }

        return $genericIcon;
    }
}
