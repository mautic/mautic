<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ReflectionHelper
{
    /**
     * Registers every #[AsEventListener] method of the subscriber on the dispatcher,
     * mirroring what the DI compiler pass does, so tests can keep dispatching events.
     */
    public static function registerAttributeListeners(EventDispatcherInterface $dispatcher, object $subscriber): void
    {
        foreach (new \ReflectionObject($subscriber)->getMethods() as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                $listener = $attribute->newInstance();
                $dispatcher->addListener($listener->event, [$subscriber, $method->getName()], $listener->priority);
            }
        }
    }

    /**
     * Returns the #[AsEventListener] bindings of a class as a sorted list of
     * [event, method, priority] triples, so tests can assert the wiring regardless of order.
     *
     * @return list<array{string, string, int}>
     */
    public static function getAttributeListeners(string $class): array
    {
        $listeners = [];
        foreach (new \ReflectionClass($class)->getMethods() as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                $listener = $attribute->newInstance();
                $listeners[] = [$listener->event, $method->getName(), $listener->priority];
            }
        }

        sort($listeners);

        return $listeners;
    }

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
     * Sets the "id" property on an entity via reflection, bypassing visibility.
     */
    public static function setEntityId(object $entity, mixed $id): void
    {
        self::setValue($entity, 'id', $id);
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
