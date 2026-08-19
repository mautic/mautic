<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Knp\Menu\MenuItem;
use Mautic\CoreBundle\Menu\MenuRenderer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
    }
}
