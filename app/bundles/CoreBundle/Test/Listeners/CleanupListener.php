<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Listeners;

use Closure;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use ReflectionObject;

/**
 * Prevents memory leaks by resetting all the test properties.
 */
class CleanupListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function endTest(Test $test, float $time): void
    {
        $reflection = new ReflectionObject($test);

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic() && 0 !== strpos($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                $this->unsetProperty($test, $property->getName());
            }
        }
    }

    private function unsetProperty(object $object, string $property): void
    {
        $closure = function (object $object) use ($property) {
            unset($object->$property);
        };

        Closure::bind($closure, null, $object)($object);
    }
}
