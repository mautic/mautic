<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\IntegrationBundle\Entity\Connector;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ConnectorIntegrationHelper
 */
class ConnectorIntegrationHelper
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
     * Get a list of connector helper classes
     *
     * @param array|string $services
     * @param array        $withFeatures
     * @param bool         $alphabetical
     *
     * @return mixed
     */
    public function getConnectorObjects($services = null, $withFeatures = null, $alphabetical = false)
    {
        static $connectors, $available;

        if (empty($connectors)) {
            $available = $connectors = array();

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

            // Scan the addons for connector classes
            foreach ($addons as $addon) {
                if (is_dir($addon['directory'] . '/Connector')) {
                    $finder = new Finder();
                    $finder->files()->name('*Connector.php')->in($addon['directory'] . '/Connector');

                    if ($alphabetical) {
                        $finder->sortByName();
                    }

                    foreach ($finder as $file) {
                        $available[] = array(
                            'connector' => substr($file->getBaseName(), 0, -11),
                            'namespace' => str_replace('MauticAddon', '', $addon['bundle'])
                        );
                    }
                }
            }

            $connectorSettings = $this->getConnectorSettings();

            // Get all the addon integrations
            foreach ($available as $a) {
                if (!isset($integrations[$a['connector']])) {
                    $class = "\\MauticAddon\\{$a['namespace']}\\Connector\\{$a['connector']}Connector";
                    $reflectionClass = new \ReflectionClass($class);
                    if ($reflectionClass->isInstantiable()) {
                        $connectors[$a['connector']] = new $class($this->factory);
                        $connectors[$a['connector']]->setIsCore(false);
                        if (!isset($connectorSettings[$a['connector']])) {
                            $connectorSettings[$a['connector']] = new Connector();
                            $connectorSettings[$a['connector']]->setName($a['connector']);
                        }
                        $connectors[$a['connector']]->setConnectorSettings($connectorSettings[$a['connector']]);
                    }
                }
            }

            if (empty($alphabetical)) {
                // Sort by priority
                uasort($connectors, function ($a, $b) {
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
            if (!is_array($services) && isset($connectors[$services])) {
                return array($services => $connectors[$services]);
            } elseif (is_array($services)) {
                $specific = array();
                foreach ($services as $s) {
                    if (isset($connectors[$s])) {
                        $specific[$s] = $connectors[$s];
                    }
                }
                return $specific;
            } else {
                throw new MethodNotAllowedHttpException($available);
            }
        } elseif (!empty($withFeatures)) {
            $specific = array();
            foreach ($connectors as $n => $d) {
                $settings = $d->getConnectorSettings();
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

        return $connectors;
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
            $integrations = $this->getConnectorObjects();
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
     * Get array of connector entities
     *
     * @return mixed
     */
    public function getConnectorSettings()
    {
        return $this->factory->getEntityManager()->getRepository('MauticIntegrationBundle:Connector')->getConnectors();
    }

    /**
     * Get the user's social profile data from cache or connectors if indicated
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $fields
     * @param bool                           $refresh
     * @param string                         $specificConnector
     * @param bool                           $persistLead
     * @param bool                           $returnSettings
     *
     * @return array
     */
    public function getUserProfiles($lead, $fields = array(), $refresh = false, $specificConnector = null, $persistLead = true, $returnSettings = false)
    {
        $socialCache      = $lead->getSocialCache();
        $featureSettings  = array();
        if ($refresh) {
            //regenerate from connectors
            $now = new DateTimeHelper();

            //check to see if there are social profiles activated
            $socialConnectors = $this->getConnectorObjects($specificConnector, array('public_profile', 'public_activity'));
            foreach ($socialConnectors as $connector => $sn) {
                $settings        = $sn->getConnectorSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = $this->getUserIdentifierField($sn, $fields);

                if ($returnSettings) {
                    $featureSettings[$connector] = $settings->getFeatureSettings();
                }

                if ($identifierField && $settings->isPublished()) {
                    $profile = (!isset($socialCache[$connector])) ? array() : $socialCache[$connector];

                    //clear the cache
                    unset($profile['profile'], $profile['activity']);

                    if (in_array('public_profile', $features)) {
                        $sn->getUserData($identifierField, $profile);
                    }

                    if (in_array('public_activity', $features)) {
                        $sn->getPublicActivity($identifierField, $profile);
                    }

                    if (!empty($profile['profile']) || !empty($profile['activity'])) {
                        if (!isset($socialCache[$connector])) {
                            $socialCache[$connector] = array();
                        }

                        $socialCache[$connector]['profile']     = (!empty($profile['profile']))  ? $profile['profile'] : array();
                        $socialCache[$connector]['activity']    = (!empty($profile['activity'])) ? $profile['activity'] : array();
                        $socialCache[$connector]['lastRefresh'] = $now->toUtcString();
                    } else {
                        unset($socialCache[$connector]);
                    }
                } elseif (isset($socialCache[$connector])) {
                    //connector is now not applicable
                    unset($socialCache[$connector]);
                }
            }

            if ($persistLead) {
                $lead->setSocialCache($socialCache);
                $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->saveEntity($lead);
            }
        } elseif ($returnSettings) {
            $socialConnectors = $this->getConnectorObjects($specificConnector, array('public_profile', 'public_activity'));
            foreach ($socialConnectors as $connector => $sn) {
                $settings                  = $sn->getConnectorSettings();
                $featureSettings[$connector] = $settings->getFeatureSettings();
            }
        }

        if ($specificConnector) {
            return ($returnSettings) ? array(array($specificConnector => $socialCache[$specificConnector]), $featureSettings)
                : array($specificConnector => $socialCache[$specificConnector]);
        }

        return ($returnSettings) ? array($socialCache, $featureSettings) : $socialCache;
    }

    /**
     * @param      $lead
     * @param bool $connector
     */
    public function clearConnectorCache($lead, $connector = false)
    {
        $socialCache = $lead->getSocialCache();
        if (!empty($connector)) {
            unset($socialCache[$connector]);
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
            $socialConnectors = $this->getConnectorObjects(null, array('share_button'), true);
            $templating     = $this->factory->getTemplating();
            foreach ($socialConnectors as $connector => $details) {
                $settings        = $details->getConnectorSettings();
                $featureSettings = $settings->getFeatureSettings();
                $apiKeys         = $settings->getApiKeys();
                $shareSettings   = isset($featureSettings['shareButton']) ? $featureSettings['shareButton'] : array();

                //add the api keys for use within the share buttons
                // TODO - The template path needs to be extended to support addons
                $shareSettings['keys'] = $apiKeys;
                $shareBtns[$connector]   = $templating->render("MauticSocialBundle:Connector/$connector:share.html.php", array(
                    'settings' => $shareSettings,
                ));
            }
        }
        return $shareBtns;
    }

    /**
     * Loops through field values available and finds the field the connector needs to obtain the user
     *
     * @param $connectorObject
     * @param $fields
     * @return bool
     */
    public function getUserIdentifierField($connectorObject, $fields)
    {
        $identifierField = $connectorObject->getIdentifierFields();
        $identifier      = (is_array($identifierField)) ? array() : false;
        $matchFound      = false;

        $findMatch = function ($f, $fields) use(&$identifierField, &$identifier, &$matchFound) {
            if (is_array($identifier)) {
                //there are multiple fields the connector can identify by
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
     * Get the path to the connector's icon relative to the site root
     *
     * @param $connector
     *
     * @return string
     */
    public function getIconPath($connector)
    {
        $systemPath  = $this->factory->getSystemPath('root');
        $genericIcon = 'app/bundles/IntegrationBundle/Assets/img/generic.png';
        $name        = $connector->getConnectorSettings()->getName();

        // For non-core bundles, we need to extract out the bundle's name to figure out where in the filesystem to look for the icon
        $className = get_class($connector);
        $exploded  = explode('\\', $className);
        $icon      = 'addons/' . $exploded[1] . '/Assets/img/' . strtolower($name) . '.png';

        if (file_exists($systemPath . '/' . $icon)) {
            return $icon;
        }

        return $genericIcon;
    }
}
