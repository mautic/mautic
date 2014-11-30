<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;

class IntegrationHelper
{
    /**
     * List mapper objects from specific application
     *
     * @param MauticFactory $factory
     * @param $application
     * @return array
     */
    static public function getMappers(MauticFactory $factory, $application)
    {
        $entities = array();
        $bundles = $factory->getParameter('bundles');
        $bundle = $bundles[ucfirst($application)];

        $finder = new Finder();
        $finder->files()->name('*Mapper.php')->in($bundle['directory'] . '/Mapper');
        $finder->sortByName();
        foreach ($finder as $file) {
            $class = sprintf('\\Mautic\%s\Mapper\%s', $bundle['bundle'], substr($file->getBaseName(), 0, -4));
            $object = new $class($factory);
            $entities[] = $object;
        }

        return $entities;
    }

    /**
     * Get a mapper object
     *
     * @param MauticFactory $factory
     * @param $application
     * @param $mapper
     * @return mixed
     */
    static public function getMapper(MauticFactory $factory, $application, $mapper)
    {
        $entities = array();
        $bundles = $factory->getParameter('bundles');
        $bundle = $bundles[ucfirst($application)];

        $class = sprintf('\\Mautic\%s\Mapper\%s', $bundle['bundle'], $mapper);
        $object = new $class($factory);

        return $object;

    }
}