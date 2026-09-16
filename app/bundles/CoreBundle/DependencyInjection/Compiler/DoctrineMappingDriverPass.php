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
     * DoctrineBundle stopped resolving the driver class from the
     * doctrine.orm.metadata.staticphp.class parameter in 2.13.1, and 3.0 removed the
     * parameter altogether, so the swap is applied to the driver definition registered
     * for each entity manager instead. Keying it off the parameter left the attributes
     * unread, and an entity whose mapping has already moved to attributes then lost it
     * silently: #[ORM\Table] falling back to the short class name, #[ORM\Id] leaving the
     * entity with no identifier at all.
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
