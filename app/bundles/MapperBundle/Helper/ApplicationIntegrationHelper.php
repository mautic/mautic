<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Helper;


class ApplicationIntegrationHelper
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
     * Get available objects for choices in the config UI
     *
     * @return mixed
     */
    public static function getAvailableObjects(MauticFactory $factory, $service = null)
    {
        static $objects = array();

        if (empty($objects)) {
            $objects = self::getApplicationObjects($factory);
        }

        return (!empty($objects)) ? $objects[$service] : $objects;
    }


}