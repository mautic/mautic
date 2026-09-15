<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Doctrine\Mapping\AttributeAndStaticPhpDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DoctrineMappingDriverPass implements CompilerPassInterface
{
    /**
     * Swaps the staticphp driver for one that also reads Doctrine attributes, so entities
     * can be migrated from loadMetadata() to attributes field by field.
     *
     * DoctrineBundle 3 dropped the doctrine.orm.metadata.staticphp.class parameter and
     * hardcodes the driver class instead, so the swap is applied to the driver definition
     * registered for each entity manager. Skipping it would leave the attributes unread,
     * and an entity whose table mapping has already moved to #[ORM\Table] would fall back
     * to its short class name - which the five entities named Stat then collide on.
     */
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (1 !== preg_match('#^doctrine\.orm\..+_staticphp_metadata_driver$#', $id)) {
                continue;
            }

            $container->getDefinition($id)->setClass(AttributeAndStaticPhpDriver::class);
        }
    }
}
