<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test\Listeners;

use LogicException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use ReflectionObject;
use ReflectionProperty;

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
                $this->assertTypeIsNullable($property);
                $property->setAccessible(true);
                $property->setValue($test, null);
            }
        }
    }

    private function assertTypeIsNullable(ReflectionProperty $property): void
    {
        if (PHP_VERSION_ID < 70400) {
            return;
        }

        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        $type = $property->getType();

        if ($type && !$type->allowsNull()) {
            throw new LogicException(sprintf('Property "%s::$%s" must be nullable to prevent memory leaks', $property->getDeclaringClass()->getName(), $property->getName()));
        }
    }
}
