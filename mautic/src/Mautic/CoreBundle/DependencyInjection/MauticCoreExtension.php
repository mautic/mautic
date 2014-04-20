<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;


/**
 * Class MauticCoreExtension
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @package Mautic\CoreBundle\DependencyInjection
 */

class MauticCoreExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        //set the parameters
        foreach ($config as $k => $v) {
            $container->setParameter("mautic.{$k}", $v);
        }

        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('events.php');
        $loader->load('forms.php');
        $loader->load('services.php');
        $loader->load('menu.php');

    }

    public function prepend(ContainerBuilder $container)
    {
        //here we can disable or modify config settings for other bundles if need be
        $bundles = $container->getParameter('mautic.bundles');

        $configs = $container->getExtensionConfig($this->getAlias());
        $config  = $this->processConfiguration(new Configuration(), $configs);

        //inject CoreBundle configuration parameters into the other Mautic bundles
        foreach ($bundles as $name => $bundle) {
            $container->prependExtensionConfig($name, $config);
        }
    }
}
