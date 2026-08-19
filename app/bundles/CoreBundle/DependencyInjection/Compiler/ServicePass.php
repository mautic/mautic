<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Knp\Menu\MenuItem;
use Mautic\CoreBundle\Menu\MenuRenderer;
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
//        $bundles = array_merge($container->getParameter('mautic.bundles'), $container->getParameter('mautic.plugin.bundles'));

        // Store menu renderer options to create unique renderering classes per menu
        // since KNP menus doesn't seem to support a Renderer factory
        $menus = [];


        // Setup default menu details
        if ('menus' == $type) {
            $details = array_merge(
                [
                    'class'   => MenuItem::class,
                    'factory' => ['@mautic.menu.builder', $details['alias'].'Menu'],
                ],
                $details
            );

            $menus[$details['alias']] = $details['options'] ?? [];
        }

        foreach ($menus as $alias => $options) {
            $container->setDefinition('mautic.menu_renderer.'.$alias, new Definition(
                MenuRenderer::class,
                [
                    new Reference('knp_menu.matcher'),
                    new Reference('twig'),
                    $options,
                ]
            ))
                ->addTag('knp_menu.renderer', ['alias' => $alias]   );
        }
    }

}
