<?php

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
    private int $level;

    /**
     * @param int         $level
     * @param string|null $path
     */
    public function __construct(
        private array $fields,
        $level = 3,
        private $path = null
    ) {
        $this->level  = (int) $level;
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
        if ($navigatorContext->getDepth() <= $this->level) {
            return false;
        }

        return true;
    }
}
