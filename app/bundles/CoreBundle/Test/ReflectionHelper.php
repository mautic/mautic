<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

final class ReflectionHelper
{
    /**
     * Sets a property value on an object via reflection, bypassing visibility.
     *
     * Resolves the declaring class by walking up the hierarchy, so private
     * properties declared on a parent class (e.g. when $object is a mock) work too.
     */
    public static function setValue(object $object, string $property, mixed $value): void
    {
        $reflectionClass = new \ReflectionClass($object);
        while (!$reflectionClass->hasProperty($property)) {
            $parent = $reflectionClass->getParentClass();
            if (false === $parent) {
                throw new \ReflectionException(sprintf('Property "%s" not found on "%s".', $property, $object::class));
            }
            $reflectionClass = $parent;
        }

        $reflectionClass->getProperty($property)->setValue($object, $value);
    }

    /**
     * Sets a static property value on a class via reflection, bypassing visibility.
     */
    public static function setStaticValue(string $class, string $property, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($class, $property);
        $reflectionProperty->setValue(null, $value);
    }
}
