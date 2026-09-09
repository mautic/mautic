<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dual mapping loader for the loadMetadata() to attribute migration.
 *
 * Entities are migrated to Doctrine attributes incrementally, so an entity may carry attributes for
 * the converted mappings while a static loadMetadata() still defines the rest. The attribute driver
 * loads the attributes; this listener then calls the remaining loadMetadata() so both contribute to
 * the same ClassMetadata. Once an entity drops loadMetadata() entirely, this becomes a no-op for it.
 */
final class LoadStaticMetadataSubscriber
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata   = $eventArgs->getClassMetadata();
        $reflectionClass = $classMetadata->getReflectionClass();

        // Only supplement entities loaded by the attribute driver; staticphp entities have their
        // loadMetadata() called by the driver itself, so calling it here would map fields twice.
        if ([] === $reflectionClass->getAttributes(ORM\Entity::class)) {
            return;
        }

        if (!$reflectionClass->hasMethod('loadMetadata')) {
            return;
        }

        $loadMetadataMethod = $reflectionClass->getMethod('loadMetadata');

        // Skip a loadMetadata() inherited from a parent; it is meant for the parent's own metadata.
        if ($loadMetadataMethod->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
            return;
        }

        $loadMetadataMethod->invoke(null, $classMetadata);
    }
}
