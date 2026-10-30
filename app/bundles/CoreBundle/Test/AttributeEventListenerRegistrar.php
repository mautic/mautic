<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Registers a listener's #[AsEventListener] methods on a dispatcher in unit
 * tests, where no container runs RegisterListenersPass.
 */
final class AttributeEventListenerRegistrar
{
    public static function register(EventDispatcherInterface $dispatcher, object $listener): void
    {
        $reflectionClass = new \ReflectionClass($listener);

        foreach ($reflectionClass->getAttributes(AsEventListener::class) as $attribute) {
            $instance          = $attribute->newInstance();
            $reflectionMethod  = $reflectionClass->getMethod($instance->method ?? '__invoke');
            self::addListener($dispatcher, $listener, $reflectionMethod, $instance);
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            foreach ($reflectionMethod->getAttributes(AsEventListener::class) as $attribute) {
                self::addListener($dispatcher, $listener, $reflectionMethod, $attribute->newInstance());
            }
        }
    }

    private static function addListener(
        EventDispatcherInterface $dispatcher,
        object $listener,
        \ReflectionMethod $reflectionMethod,
        AsEventListener $attribute,
    ): void {
        $event = $attribute->event ?? self::inferEventFromMethod($reflectionMethod);

        $dispatcher->addListener($event, [$listener, $reflectionMethod->getName()], $attribute->priority);
    }

    private static function inferEventFromMethod(\ReflectionMethod $reflectionMethod): string
    {
        $parameter = $reflectionMethod->getParameters()[0] ?? null;
        $type      = $parameter?->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException(sprintf(
                'Cannot infer the event for "%s::%s()". Add event: to #[AsEventListener] or type-hint the first parameter.',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName(),
            ));
        }

        return $type->getName();
    }
}
