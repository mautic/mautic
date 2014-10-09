<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection;

use Mautic\CoreBundle\Helper\ServiceLoaderHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class MauticCoreExtension
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @package Mautic\CoreBundle\DependencyInjection
 */

class MauticCoreExtension extends Extension
{

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('mautic.bundles');

        foreach ($bundles as $name => $bundle) {
            //load services
            $directory = $bundle['directory'] . '/Config/services';
            if (file_exists($directory)) {

                //PHP config files
                $finder = new Finder();
                $finder->files()->in($directory)->name('*.php');
                if (count($finder)) {
                    $loader = new Loader\PhpFileLoader($container, new FileLocator($directory));
                    foreach ($finder as $file) {
                        $loader->load($file->getFilename());
                    }
                }

                //YAML config files
                $finder = new Finder();
                $finder->files()->in($directory)->name('*.yaml');
                if (count($finder)) {
                    $loader = new Loader\YamlFileLoader($container, new FileLocator($directory));
                    foreach ($finder as $file) {
                        $loader->load($file->getFilename());
                    }
                }

                //XML config files
                $finder = new Finder();
                $finder->files()->in($directory)->name('*.xml');
                if (count($finder)) {
                    $loader = new Loader\XmlFileLoader($container, new FileLocator($directory));
                    foreach ($finder as $file) {
                        $loader->load($file->getFilename());
                    }
                }
            }
        }
    }
}
