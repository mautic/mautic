<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Doctrine\Mapping\AttributeAndStaticPhpDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DoctrineMappingDriverPass implements CompilerPassInterface
{
    // Swaps the staticphp driver for one that also reads Doctrine attributes, so entities
    // can be migrated from loadMetadata() to attributes field by field.
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('doctrine.orm.metadata.staticphp.class')) {
            return;
        }

        $container->setParameter('doctrine.orm.metadata.staticphp.class', AttributeAndStaticPhpDriver::class);
    }
}
