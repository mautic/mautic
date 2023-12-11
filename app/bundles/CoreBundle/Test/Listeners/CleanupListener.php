<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Listeners;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Prevents memory leaks by resetting all the test properties.
 */
class CleanupListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function endTest(Test $test, float $time): void
    {
        $reflection = new \ReflectionObject($test);

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic() && !str_starts_with($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                $this->unsetProperty($test, $property->getName());
            }
        }
    }

    private function unsetProperty(object $object, string $property): void
    {
        $closure = function (object $object) use ($property): void {
            unset($object->$property);
        };

        \Closure::bind($closure, null, $object)($object);
    }
}
