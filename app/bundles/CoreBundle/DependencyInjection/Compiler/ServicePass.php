<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

final class ServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $bundles = array_merge($container->getParameter('mautic.bundles'), $container->getParameter('mautic.plugin.bundles'));

        // Store menu renderer options to create unique renderering classes per menu
        // since KNP menus doesn't seem to support a Renderer factory
        $menus = [];

        // Keep track of names used to prevent overwriting any and thus losing functionality
        $serviceNames = [];

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['services'])) {
                $config = $bundle['config']['services'];
                foreach ($config as $type => $services) {
                    switch ($type) {
                        case 'events':
                            $defaultTag = 'kernel.event_subscriber';
                            break;
                        case 'forms':
                            $defaultTag = 'form.type';
                            break;
                        case 'helpers':
                            $defaultTag = 'twig.helper';
                            break;
                        case 'menus':
                            $defaultTag = 'knp_menu.menu';
                            break;
                        case 'models':
                            $defaultTag = 'mautic.model';
                            @trigger_error('Setting "models" in config is deprecated. Convert to using autowiring.', E_USER_DEPRECATED);
                            break;
                        case 'permissions':
                            $defaultTag = 'mautic.permissions';
                            break;
                        case 'integrations':
                            $defaultTag = 'mautic.integration';
                            break;
                        case 'controllers':
                            $defaultTag = 'controller.service_arguments';
                            break;
                        default:
                            $defaultTag = false;
                            break;
                    }

                    foreach ($services as $name => $details) {
                        if (isset($serviceNames[$name])) {
                            throw new \InvalidArgumentException("$name is already registered");
                        }
                        $serviceNames[$name] = true;

                        if (!is_array($details)) {
                            // Set parameter
                            $container->setParameter($name, $details);
                            continue;
                        }

                        // Setup default menu details
                        if ('menus' == $type) {
                            $details = array_merge(
                                [
                                    'class'   => \Knp\Menu\MenuItem::class,
                                    'factory' => ['@mautic.menu.builder', $details['alias'].'Menu'],
                                ],
                                $details
                            );

                            $menus[$details['alias']] = $details['options'] ?? [];
                        }

                        // Set service alias
                        $alias = new Alias($name);
                        $alias->setPublic(true);
                        if (isset($details['serviceAlias'])) {
                            // Fix escaped sprintf placeholders
                            $details['serviceAlias'] = str_replace('%%', '%', $details['serviceAlias']);
                            $container->setAlias(sprintf($details['serviceAlias'], $name), $alias);
                        } elseif (isset($details['serviceAliases'])) {
                            foreach ($details['serviceAliases'] as $aliasName) {
                                $aliasName = str_replace('%%', '%', $aliasName);
                                $container->setAlias(sprintf($aliasName, $name), $alias);
                            }
                        }
                        // Symfony 4 is requiring the classname for some auto-wired services (controllers)
                        if ($name !== $details['class']) {
                            $container->setAlias($details['class'], $alias);
                        }

                        // Generate definition arguments
                        $definitionArguments = [];
                        if (!isset($details['arguments'])) {
                            $details['arguments'] = [];
                        } elseif (!is_array($details['arguments'])) {
                            $details['arguments'] = [$details['arguments']];
                        }

                        foreach ($details['arguments'] as $argument) {
                            $this->processArgument($argument, $container, $definitionArguments);
                        }

                        if ($container->hasDefinition($details['class'])) {
                            $definition = $container->getDefinition($details['class']);

                            if ($definitionArguments) {
                                $definition->setArguments($definitionArguments);
                            }
                        } else {
                            $definition = new Definition($details['class'], $definitionArguments);
                            $container->setDefinition($name, $definition);
                        }

                        if (!$container->hasDefinition($name) && !$container->hasAlias($name) && $name !== $details['class']) {
                            $container->setAlias($name, new Alias($details['class'], true));
                        }

                        // Generate tag and tag arguments
                        if (isset($details['tags'])) {
                            $tagArguments = (!empty($details['tagArguments'])) ? $details['tagArguments'] : [];
                            foreach ($details['tags'] as $k => $tag) {
                                if (!isset($tagArguments[$k])) {
                                    $tagArguments[$k] = [];
                                }

                                if (!empty($details['alias'])) {
                                    $tagArguments[$k]['alias'] = $details['alias'];
                                }

                                $definition->addTag($tag, $tagArguments[$k]);
                            }
                        } else {
                            $tag          = (!empty($details['tag'])) ? $details['tag'] : $defaultTag;
                            $tagArguments = (!empty($details['tagArguments'])) ? $details['tagArguments'] : [];

                            if (!empty($tag)) {
                                if (!empty($details['alias'])) {
                                    $tagArguments['alias'] = $details['alias'];
                                }

                                $definition->addTag($tag, $tagArguments);
                            }

                            if ('events' == $type) {
                                $definition->addTag('mautic.event_subscriber');
                            }
                        }

                        // Default to a public service
                        $public = $details['public'] ?? true;
                        $definition->setPublic($public);

                        // Set lazy service
                        if (!empty($details['lazy'])) {
                            $definition->setLazy($details['lazy']);
                        }

                        // Set synthetic service
                        if (!empty($details['synthetic'])) {
                            $definition->setSynthetic($details['synthetic']);
                        }

                        // Set abstract service
                        if (!empty($details['abstract'])) {
                            $definition->setAbstract($details['abstract']);
                        }

                        // Set include file
                        if (!empty($details['file'])) {
                            $definition->setFile($details['file']);
                        }

                        // Set service configurator
                        if (!empty($details['configurator'])) {
                            $definition->setConfigurator($details['configurator']);
                        }

                        // Set factory - Preferred API since Symfony 2.6
                        if (!empty($details['factory'])) {
                            $factory = $details['factory'];

                            /*
                             * Standardize to an array then convert a service to a Reference if needed
                             *
                             * This supports three syntaxes:
                             *
                             * 1) @service::method or Class::method
                             * 2) array('@service', 'method') or array('Class', 'method')
                             * 3) "Unknown" - Just pass it to the definition
                             *
                             * Services must always be prefaced with an @ symbol (similar to "normal" config files)
                             */
                            if (is_string($factory) && str_contains($factory, '::')) {
                                $factory = explode('::', $factory, 2);
                            }

                            // Check if the first item in the factory array is a service and if so fetch its reference
                            if (is_array($factory) && str_starts_with($factory[0], '@')) {
                                // Exclude the leading @ character in the service ID
                                $factory[0] = new Reference(substr($factory[0], 1));
                            }

                            $definition->setFactory($factory);
                        }

                        // Set method calls
                        if (!empty($details['methodCalls'])) {
                            foreach ($details['methodCalls'] as $method => $methodArguments) {
                                $methodCallArguments = [];
                                foreach ($methodArguments as $argument) {
                                    $this->processArgument($argument, $container, $methodCallArguments);
                                }

                                $definition->addMethodCall($method, $methodCallArguments);
                            }
                        }

                        // Set deprecated service
                        if (!empty($details['decoratedService'])) {
                            // This should be an array and the first parameter cannot be empty
                            if (!is_array($details['decoratedService'])) {
                                throw new InvalidArgumentException('The "decoratedService" definition must be an array.');
                            }

                            // The second parameter of setDecoratedService is optional, check if there is a second key in the array
                            $secondParam = !empty($details['decoratedService'][1]) ? $details['decoratedService'][1] : null;

                            $definition->setDecoratedService($details['decoratedService'][0], $secondParam);
                        }

                        unset($definition);
                    }
                }
            }
        }

        foreach ($menus as $alias => $options) {
            $container->setDefinition('mautic.menu_renderer.'.$alias, new Definition(
                \Mautic\CoreBundle\Menu\MenuRenderer::class,
                [
                    new Reference('knp_menu.matcher'),
                    new Reference('twig'),
                    $options,
                ]
            ))
                ->addTag('knp_menu.renderer',
                    [
                        'alias' => $alias,
                    ]
                );
        }

        unset($bundles);
    }

    /**
     * @param mixed   $argument
     * @param mixed[] $definitionArguments
     */
    private function processArgument($argument, ContainerBuilder $container, &$definitionArguments): void
    {
        if ('' === $argument) {
            // To be added during compilation
            $definitionArguments[] = '';
        } elseif (is_array($argument) || is_object($argument)) {
            foreach ($argument as &$v) {
                if (str_starts_with($v, '%')) {
                    $v = str_replace('%%', '%', $v);
                    $v = $container->getParameter(substr($v, 1, -1));
                }
            }
            $definitionArguments[] = $argument;
        } elseif (str_starts_with($argument, '%')) {
            // Parameter
            $argument              = str_replace('%%', '%', $argument);
            $definitionArguments[] = $container->getParameter(substr($argument, 1, -1));
        } elseif (is_bool($argument) || str_contains($argument, '\\')) {
            // Parameter or Class
            $definitionArguments[] = $argument;
        } elseif (str_starts_with($argument, '"')) {
            // String
            $definitionArguments[] = substr($argument, 1, -1);
        } elseif (str_starts_with($argument, '@=')) {
            // Expression
            $argument              = substr($argument, 2);
            $definitionArguments[] = new Expression($argument);
        } elseif (str_starts_with($argument, '@')) {
            // Service
            $argument              = substr($argument, 1);
            $definitionArguments[] = new Reference($argument);
        } else {
            // Reference
            $definitionArguments[] = new Reference($argument);
        }
    }
}
