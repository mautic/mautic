<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Helper;

use Mautic\AddonBundle\Entity\Addon;
use Mautic\AddonBundle\Entity\Integration;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class IntegrationHelper
 */
class IntegrationHelper
{

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get a list of integration helper classes
     *
     * @param array|string $services
     * @param array        $withFeatures
     * @param bool         $alphabetical
     * @param null|int     $addonFilter
     *
     * @return mixed
     */
    public function getIntegrationObjects ($services = null, $withFeatures = null, $alphabetical = false, $addonFilter = null)
    {
        static $integrations, $available;

        if (empty($integrations)) {
            $em = $this->factory->getEntityManager();

            $available = $integrations = array();

            // And we'll be scanning the addon bundles for additional classes, so have that data on standby
            $addons = $this->factory->getEnabledAddons();

            // Quickly figure out which addons are enabled so we only process those
            /** @var \Mautic\AddonBundle\Entity\AddonRepository $addonRepo */
            $addonRepo     = $em->getRepository('MauticAddonBundle:Addon');
            $addonStatuses = $addonRepo->getBundleStatus(true);

            // Scan the addons for integration classes
            foreach ($addons as $addon) {
                if (is_dir($addon['directory'] . '/Integration')) {
                    $finder = new Finder();
                    $finder->files()->name('*Integration.php')->in($addon['directory'] . '/Integration')->ignoreDotFiles(true);

                    if ($alphabetical) {
                        $finder->sortByName();
                    }

                    $id = $addonStatuses[$addon['bundle']]['id'];
                    foreach ($finder as $file) {
                        $available[] = array(
                            'addon'       => $em->getReference('MauticAddonBundle:Addon', $id),
                            'integration' => substr($file->getBaseName(), 0, -15),
                            'namespace'   => str_replace('MauticAddon', '', $addon['bundle'])
                        );
                    }
                }
            }

            $integrationSettings = $this->getIntegrationSettings();

            // Get all the addon integrations
            foreach ($available as $id => $a) {
                if ($addonFilter && $a['addon']->getId() != $addonFilter) {
                    continue;
                }
                if (!isset($integrations[$a['integration']])) {
                    $class           = "\\MauticAddon\\" . $a['namespace'] . "\\Integration\\" . $a['integration'] . "Integration";
                    $reflectionClass = new \ReflectionClass($class);
                    if ($reflectionClass->isInstantiable()) {
                        $integrations[$a['integration']] = new $class($this->factory);
                        if (!isset($integrationSettings[$a['integration']])) {
                            $integrationSettings[$a['integration']] = new Integration();
                            $integrationSettings[$a['integration']]->setName($a['integration']);
                        }
                        $integrationSettings[$a['integration']]->setAddon($a['addon']);

                        $integrations[$a['integration']]->setIntegrationSettings($integrationSettings[$a['integration']]);
                    }
                }
            }

            if (empty($alphabetical)) {
                // Sort by priority
                uasort($integrations, function ($a, $b) {
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
            if (!is_array($services) && isset($integrations[$services])) {
                return array($services => $integrations[$services]);
            } elseif (is_array($services)) {
                $specific = array();
                foreach ($services as $s) {
                    if (isset($integrations[$s])) {
                        $specific[$s] = $integrations[$s];
                    }
                }

                return $specific;
            } else {
                throw new MethodNotAllowedHttpException(array_keys($available));
            }
        } elseif (!empty($withFeatures)) {
            if (!is_array($withFeatures)) {
                $withFeatures = array($withFeatures);
            }

            $specific = array();
            foreach ($integrations as $n => $d) {
                $settings = $d->getIntegrationSettings();
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

        return $integrations;
    }

    /**
     * Get a single integration object
     *
     * @param $name
     *
     * @return mixed
     */
    public function getIntegrationObject($name)
    {
        $integrationObjects = $this->getIntegrationObjects($name);
        return $integrationObjects[$name];
    }

    /**
     * Gets a count of integrations
     *
     * @param $addon
     *
     * @return int
     */
    public function getIntegrationCount ($addon)
    {
        if (!is_array($addon)) {
            $addons = $this->factory->getParameter('addon.bundles');
            if (array_key_exists($addon, $addons)) {
                $addon = $addons[$addon];
            } else {
                // It doesn't exist so return 0

                return 0;
            }
        }

        if (is_dir($addon['directory'] . '/Integration')) {
            $finder = new Finder();
            $finder->files()->name('*Integration.php')->in($addon['directory'] . '/Integration')->ignoreDotFiles(true);

            return iterator_count($finder);
        }

        return 0;
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
    public function getSocialProfileUrlRegex ($find = true)
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
     * Get array of integration entities
     *
     * @return mixed
     */
    public function getIntegrationSettings ()
    {
        return $this->factory->getEntityManager()->getRepository('MauticAddonBundle:Integration')->getIntegrations();
    }

    /**
     * Get the user's social profile data from cache or integrations if indicated
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $fields
     * @param bool                           $refresh
     * @param string                         $specificIntegration
     * @param bool                           $persistLead
     * @param bool                           $returnSettings
     *
     * @return array
     */
    public function getUserProfiles ($lead, $fields = array(), $refresh = false, $specificIntegration = null, $persistLead = true, $returnSettings = false)
    {
        $socialCache     = $lead->getSocialCache();
        $featureSettings = array();
        if ($refresh) {
            //regenerate from integrations
            $now = new DateTimeHelper();

            //check to see if there are social profiles activated
            $socialIntegrations = $this->getIntegrationObjects($specificIntegration, array('public_profile', 'public_activity'));

            /* @var \MauticAddon\MauticSocialBundle\Integration\SocialIntegration $sn */
            foreach ($socialIntegrations as $integration => $sn) {
                $settings        = $sn->getIntegrationSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = $this->getUserIdentifierField($sn, $fields);

                if ($returnSettings) {
                    $featureSettings[$integration] = $settings->getFeatureSettings();
                }

                if ($identifierField && $settings->isPublished()) {
                    $profile = (!isset($socialCache[$integration])) ? array() : $socialCache[$integration];

                    //clear the cache
                    unset($profile['profile'], $profile['activity']);

                    if (in_array('public_profile', $features) && $sn->isAuthorized()) {
                        $sn->getUserData($identifierField, $profile);
                    }

                    if (in_array('public_activity', $features) && $sn->isAuthorized()) {
                        $sn->getPublicActivity($identifierField, $profile);
                    }

                    if (!empty($profile['profile']) || !empty($profile['activity'])) {
                        if (!isset($socialCache[$integration])) {
                            $socialCache[$integration] = array();
                        }

                        $socialCache[$integration]['profile']     = (!empty($profile['profile'])) ? $profile['profile'] : array();
                        $socialCache[$integration]['activity']    = (!empty($profile['activity'])) ? $profile['activity'] : array();
                        $socialCache[$integration]['lastRefresh'] = $now->toUtcString();
                    } else {
                        unset($socialCache[$integration]);
                    }
                } elseif (isset($socialCache[$integration])) {
                    //integration is now not applicable
                    unset($socialCache[$integration]);
                }
            }

            if ($persistLead) {
                $lead->setSocialCache($socialCache);
                $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        } elseif ($returnSettings) {
            $socialIntegrations = $this->getIntegrationObjects($specificIntegration, array('public_profile', 'public_activity'));
            foreach ($socialIntegrations as $integration => $sn) {
                $settings                      = $sn->getIntegrationSettings();
                $featureSettings[$integration] = $settings->getFeatureSettings();
            }
        }

        if ($specificIntegration) {
            return ($returnSettings) ? array(array($specificIntegration => $socialCache[$specificIntegration]), $featureSettings)
                : array($specificIntegration => $socialCache[$specificIntegration]);
        }

        return ($returnSettings) ? array($socialCache, $featureSettings) : $socialCache;
    }

    /**
     * @param      $lead
     * @param bool $integration
     */
    public function clearIntegrationCache ($lead, $integration = false)
    {
        $socialCache = $lead->getSocialCache();
        if (!empty($integration)) {
            unset($socialCache[$integration]);
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
    public function getShareButtons ()
    {
        static $shareBtns = array();

        if (empty($shareBtns)) {
            $socialIntegrations = $this->getIntegrationObjects(null, array('share_button'), true);
            $templating         = $this->factory->getTemplating();

            /**
             * @var  string                                              $integration
             * @var  \Mautic\AddonBundle\Integration\AbstractIntegration $details
             */
            foreach ($socialIntegrations as $integration => $details) {
                /** @var \Mautic\AddonBundle\Entity\Integration $settings */
                $settings = $details->getIntegrationSettings();

                $featureSettings = $settings->getFeatureSettings();
                $apiKeys         = $details->decryptApiKeys($settings->getApiKeys());
                $addon           = $settings->getAddon();
                $shareSettings   = isset($featureSettings['shareButton']) ? $featureSettings['shareButton'] : array();

                //add the api keys for use within the share buttons
                $shareSettings['keys']   = $apiKeys;
                $shareBtns[$integration] = $templating->render($addon->getBundle() . ":Integration/$integration:share.html.php", array(
                    'settings' => $shareSettings,
                ));
            }
        }

        return $shareBtns;
    }

    /**
     * Loops through field values available and finds the field the integration needs to obtain the user
     *
     * @param $integrationObject
     * @param $fields
     *
     * @return bool
     */
    public function getUserIdentifierField ($integrationObject, $fields)
    {
        $identifierField = $integrationObject->getIdentifierFields();
        $identifier      = (is_array($identifierField)) ? array() : false;
        $matchFound      = false;

        $findMatch = function ($f, $fields) use (&$identifierField, &$identifier, &$matchFound) {
            if (is_array($identifier)) {
                //there are multiple fields the integration can identify by
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
     * Get the path to the integration's icon relative to the site root
     *
     * @param $integration
     *
     * @return string
     */
    public function getIconPath ($integration)
    {
        $systemPath  = $this->factory->getSystemPath('root');
        $genericIcon = 'app/bundles/AddonBundle/Assets/img/generic.png';
        $name        = $integration->getIntegrationSettings()->getName();

        // For non-core bundles, we need to extract out the bundle's name to figure out where in the filesystem to look for the icon
        $className = get_class($integration);
        $exploded  = explode('\\', $className);
        $icon      = 'addons/' . $exploded[1] . '/Assets/img/' . strtolower($name) . '.png';

        if (file_exists($systemPath . '/' . $icon)) {
            return $icon;
        }

        return $genericIcon;
    }
}