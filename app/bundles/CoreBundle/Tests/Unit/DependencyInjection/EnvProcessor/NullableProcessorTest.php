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

use Mautic\CoreBundle\DependencyInjection\EnvProcessor\NullableProcessor;
use PHPUnit\Framework\TestCase;

class NullableProcessorTest extends TestCase
{
    public function testNullReturnedIfEmptyString()
    {
        $getEnv = function (string $name) {
            return '';
        };

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertNull($value);
    }

    public function testValueReturnedIfNotEmptyString()
    {
        $getEnv = function (string $name) {
            return 'foobar';
        };

        $processor = new NullableProcessor();

        $value = $processor->getEnv('', 'test', $getEnv);

        $this->assertEquals('foobar', $value);
    }
}
