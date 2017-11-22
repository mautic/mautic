<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\CustomFieldHelper;

class CustomFieldHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testFixValueTypeForBooleans()
    {
        $this->assertNull(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, null));
        $this->assertTrue(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, 1));
        $this->assertTrue(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, true));
        $this->assertTrue(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, '1'));
        $this->assertFalse(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, '0'));
        $this->assertFalse(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, ''));
        $this->assertFalse(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, false));
        $this->assertFalse(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_BOOLEAN, 0));
    }

    public function testFixValueTypeForNumbers()
    {
        $this->assertNull(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, null));
        $this->assertEquals(1, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, 1));
        $this->assertEquals(1, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, true));
        $this->assertEquals(0, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, false));
        $this->assertEquals(5, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, '5'));
        $this->assertEquals(0, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, ''));
        $this->assertEquals(0, CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_NUMBER, '0'));
    }

    public function testFixValueTypeForSelect()
    {
        $this->assertNull(CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, null));
        $this->assertEquals('1', CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, true));
        $this->assertEquals('', CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, false));
        $this->assertEquals('1', CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, 1));
        $this->assertEquals('1', CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, '1'));
        $this->assertEquals('one', CustomFieldHelper::fixValueType(CustomFieldHelper::TYPE_SELECT, 'one'));
    }
}
