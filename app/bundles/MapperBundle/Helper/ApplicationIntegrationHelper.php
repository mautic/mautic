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
use Symfony\Component\Finder\Finder;

class ApplicationIntegrationHelper
{

    static $factory;

    public static function getApplications(MauticFactory $factory, $services = null, $withFeatures = null, $alphabetical = false)
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
            $available[] = basename($file->getBaseName(),'.php');
        }

        if (empty($integrations)) {
            foreach ($available as $a) {
                if (!isset($integrations[$a])) {
                    $class = "\\Mautic\\MapperBundle\\Integration\\{$a}";
                    $integrations[$a] = new $class($factory);
                }
            }
        }

        return $integrations;
    }
}