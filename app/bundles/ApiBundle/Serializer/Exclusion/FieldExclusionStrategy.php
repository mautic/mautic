<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Exclude specific fields at a specific level.
 */
class FieldExclusionStrategy implements ExclusionStrategyInterface
{
    public function __construct(
        private readonly array $fields,
        private readonly int $level = 3,
        private readonly ?string $path = null,
    ) {
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ($this->path) {
            $path = implode('.', $navigatorContext->getCurrentPath());
            if ($path !== $this->path) {
                return false;
            }
        }

        $name = $property->serializedName ?: $property->name;
        if (!in_array($name, $this->fields)) {
            return false;
        }

        // children of children or parents of chidlren will be more than 3 levels deep
        return $navigatorContext->getDepth() > $this->level;
    }
}
