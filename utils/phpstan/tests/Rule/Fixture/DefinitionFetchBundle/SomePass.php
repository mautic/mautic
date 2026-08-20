<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SomePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('mautic.string.helper')->setArgument('$prefix', 'x');
        $container->getDefinition('mautic.aliased.helper')->setArgument('$prefix', 'y');
        $container->getDefinition('mautic.unknown.service')->setArgument('$prefix', 'z');
    }
}
