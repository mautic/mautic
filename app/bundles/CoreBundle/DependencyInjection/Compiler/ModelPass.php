<?php

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Factory\ModelFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ModelPass implements CompilerPassInterface
{
    public const TAG = 'mautic.model';

    public function process(ContainerBuilder $container): void
    {
        $modelServices = [];
        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $modelServices[$id] = new Reference($id);

            // because aliases are not tagged we need to inject them too.
            // @see https://github.com/symfony/symfony/issues/17256
            foreach ($container->getAliases() as $aliasId => $alias) {
                $aliasedId = (string) $alias;
                if ($aliasedId !== $id) {
                    continue;
                }

                $modelServices[$aliasId] = new Reference($aliasedId);
            }
        }

        $myService = $container->findDefinition(ModelFactory::class);

        $myService->addArgument(ServiceLocatorTagPass::register($container, $modelServices));
    }
}
