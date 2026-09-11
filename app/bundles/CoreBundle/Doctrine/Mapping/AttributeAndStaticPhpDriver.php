<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;

// Reads Doctrine mapping attributes and the legacy static loadMetadata() side by side,
// so entities can be migrated from loadMetadata() to attributes field by field.
final readonly class AttributeAndStaticPhpDriver implements MappingDriver
{
    private StaticPHPDriver $staticPhpDriver;

    private AttributeDriver $attributeDriver;

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->staticPhpDriver = new StaticPHPDriver($paths);
        $this->attributeDriver = new AttributeDriver($paths);
    }

    public function loadMetadataForClass(string $className, ClassMetadata $metadata): void
    {
        // Attributes first so that any leftover loadMetadata() calls can still add or override.
        // if (!$this->attributeDriver->isTransient($className)) {
            $this->attributeDriver->loadMetadataForClass($className, $metadata);
        //}

        if (method_exists($className, 'loadMetadata')) {
            $className::loadMetadata($metadata);
        }
    }

    public function getAllClassNames(): array
    {
        return array_values(array_unique(array_merge(
            $this->staticPhpDriver->getAllClassNames(),
            $this->attributeDriver->getAllClassNames(),
        )));
    }

    public function isTransient(string $className): bool
    {
        return $this->staticPhpDriver->isTransient($className)
            && $this->attributeDriver->isTransient($className);
    }
}
