<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class ServiceLoaderHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class ServiceLoaderHelper
{

    /**
     * Loads bundle service files
     *
     * @param                  $dir
     * @param ContainerBuilder $container
     */
    public function loadServices($dir, ContainerBuilder $container)
    {
        $serviceDir = $dir.'/../Resources/config/services';
        $loader = new Loader\PhpFileLoader($container, new FileLocator($serviceDir));
        $finder = new Finder();
        $finder->files()->in($serviceDir)->name('*.php');

        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }
    }
}