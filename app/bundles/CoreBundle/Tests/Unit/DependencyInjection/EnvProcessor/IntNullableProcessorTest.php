<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\IntNullableProcessor;
use PHPUnit\Framework\TestCase;

class IntNullableProcessorTest extends TestCase
{
    public function testNullReturnedIfNullValue()
    {
        $getEnv = function (string $name) {
            return null;
        };

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testIntReturnedIfNotNull()
    {
        $getEnv = function (string $name) {
            return '0';
        };

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfEmptyString()
    {
        $getEnv = function (string $name) {
            return '';
        };

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(0, $value);
    }

    public function testIntReturnedIfInt()
    {
        $getEnv = function (string $name) {
            return 12;
        };

        $processor = new IntNullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertSame(12, $value);
    }
}
