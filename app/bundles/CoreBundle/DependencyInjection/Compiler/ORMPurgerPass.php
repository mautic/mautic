<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Doctrine\Common\DataFixtures\Purger\ORMPurgerFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ORMPurgerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('doctrine.fixtures.purger.orm_purger_factory')) {
            return;
        }

        $definition = $container->getDefinition('doctrine.fixtures.purger.orm_purger_factory');
        $definition->setClass(ORMPurgerFactory::class);
        $definition->setArgument('$eventDispatcher', new Reference('event_dispatcher'));
    }
}
