<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Integration\AbstractIntegration;
use Symfony\Component\Finder\Finder;

abstract class IntegrationsHelper
{

    static $factory;

    /**
     * @param MauticFactory $factory
     * @param null $services
     * @param null $withFeatures
     * @param bool $alphabetical
     * @return mixed
     */
    public static function getApplications(MauticFactory $factory, $alphabetical = false)
    {
        static $integrations;

        static::$factory = $factory;
        $finder = new Finder();
        $finder->files()->name('*Integration.php')->in(__DIR__ . '/../Integration')->notName('AbstractIntegration.php');
        if ($alphabetical) {
            $finder->sortByName();
        }
        $available = array();
        foreach ($finder as $file) {
            $available[] = substr(basename($file->getBaseName(),'.php'),0, -11);
        }

        if (empty($integrations)) {
            foreach ($available as $a) {
                $key = strtolower($a);
                if (!isset($integrations[$key])) {
                    $class = "\\Mautic\\MapperBundle\\Integration\\{$a}Integration";
                    $integrations[$key] = new $class($factory);
                }
            }
        }

        return $integrations;
    }

    /**
     * @param MauticFactory $factory
     * @param $application
     * @return AbstractIntegration
     * @throws \RuntimeException
     */
    public static function getApplication(MauticFactory $factory, $application)
    {
        $integrations = self::getApplications($factory, true);

        if (array_key_exists($application, $integrations)) {
            return $integrations[$application];
        }

        return false;
    }
}