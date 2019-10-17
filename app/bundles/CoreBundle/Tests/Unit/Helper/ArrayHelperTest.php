<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\ArrayHelper;

class ArrayHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetValue()
    {
        $origin = ['one', 'two' => 'three'];

        $this->assertSame('one', ArrayHelper::getValue(0, $origin));
        $this->assertSame('three', ArrayHelper::getValue('two', $origin));
        $this->assertSame(null, ArrayHelper::getValue('five', $origin));
        $this->assertSame('default', ArrayHelper::getValue('five', $origin, 'default'));
    }

    public function testPickValue()
    {
        $origin = ['one', 'two' => 'three', 'four' => null];

        $this->assertSame('one', ArrayHelper::pickValue(0, $origin));
        $this->assertSame(['two' => 'three', 'four' => null], $origin);
        $this->assertSame('three', ArrayHelper::pickValue('two', $origin));
        $this->assertSame(['four' => null], $origin);
        $this->assertSame(null, ArrayHelper::pickValue('five', $origin));
        $this->assertSame('default', ArrayHelper::pickValue('five', $origin, 'default'));
        $this->assertSame(null, ArrayHelper::pickValue('four', $origin, 'default'));
        $this->assertSame([], $origin);
    }

    public function testSelect()
    {
        $origin = ['one', 'two' => 'three', 'four' => 'five'];

        $this->assertSame(['two' => 'three'], ArrayHelper::select(['two'], $origin));
        $this->assertSame(['two' => 'three', 'four' => 'five'], ArrayHelper::select(['two', 'four'], $origin));
        $this->assertSame(['one', 'two' => 'three'], ArrayHelper::select(['two', 0], $origin));
    }
}
