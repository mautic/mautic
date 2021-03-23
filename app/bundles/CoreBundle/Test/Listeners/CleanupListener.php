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
                $property->setAccessible(true);
                $property->setValue($test, null);
            }
        }
    }
}
