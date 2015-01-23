<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection;

use Mautic\CoreBundle\Helper\ServiceLoaderHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class MauticCoreExtension
 *
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MauticCoreExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles       = $container->getParameter('mautic.bundles');

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['services'])) {
                // Bundle has a single config file
                $this->loadConfig($container, $bundle['config']);
            } else {
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

            $addons  = $container->getParameter('mautic.addon.bundles');
            foreach ($addons as $bundle) {
                if (!empty($bundle['config']['services'])) {
                    // Bundle has a single config file
                    $this->loadConfig($container, $bundle['config']);
                } else {
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
    }

    /**
     * Register services from Mautic bundle config file
     *
     * @param $container
     * @param $config
     */
    private function loadConfig($container, $config)
    {

        foreach ($config['services'] as $type => $services) {
            switch ($type) {
                case 'events':
                    $defaultTag = 'kernel.event_subscriber';
                    break;
                case 'forms':
                    $defaultTag = 'form.type';
                    break;
                default:
                    $defaultTag = false;
                    break;
            }

            foreach ($services as $name => $details) {
                // Generate definition arguments
                $definitionArguments = array();
                if (isset($details['references'])) {
                    if (is_array($details['references'])) {
                        foreach ($details['references'] as $reference) {
                            $definitionArguments[] = new Reference($reference);
                        }
                    } else {
                        $definitionArguments[] = new Reference($details['references']);
                    }
                }
                if (isset($details['parameters'])) {
                    if (is_array($details['parameters'])) {
                        foreach ($details['parameters'] as $param) {
                            if (strpos($param, '%' !== 0)) {
                                $param = "%{$param}%";
                            }
                            $definitionArguments[] = $param;
                        }
                    } else {
                        if (strpos($details['parameters'], '%' !== 0)) {
                            $details['parameters'] = "%{$details['parameters']}%";
                        }
                        $definitionArguments[] = $details['parameters'];
                    }
                }

                // Generate tag and tag arguments
                $tag                 = (!empty($details['tag'])) ? $details['tag'] : $defaultTag;
                $tagArguments        = (!empty($details['tagArguments'])) ? $details['tagArguments'] : array();
                if (!empty($details['alias'])) {
                    $tagArguments['alias'] = $details['alias'];
                }

                // Add the service
                $definition = new Definition(
                    $details['definition'],
                    $definitionArguments
                );

                if (!empty($tag)) {
                    $definition->addTag($tag, $tagArguments);
                }

                if (!empty($details['scope'])) {
                    $definition->setScope($details['scope']);
                }

                $container->setDefinition($name, $definition);
                unset($definition);
            }
        }
    }
}
