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
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Validator\Tests\Fixtures\Reference;

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
                $loader = new Loader\PhpFileLoader($container, new FileLocator($directory));
                $finder = new Finder();
                $finder->files()->in($directory)->name('*.php');

                foreach ($finder as $file) {
                    $loader->load($file->getFilename());
                }
            }
        }
    }
}
