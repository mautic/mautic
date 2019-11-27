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

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;

class CustomFieldValueHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeValueBooleans()
    {
        $field = [
            'type'      => CustomFieldValueHelper::TYPE_BOOLEAN,
            'value'     => 1,
            'properties'=> 'a:2:{s:2:"no";s:2:"No";s:3:"yes";s:3:"Yes";}',
        ];
        $this->assertEquals('Yes', CustomFieldValueHelper::normalizeValue($field));

        $field['value'] = 0;
        $this->assertEquals('No', CustomFieldValueHelper::normalizeValue($field));
    }

    public function testNormalizeValueSelect()
    {
        $field = [
            'type'      => CustomFieldValueHelper::TYPE_SELECT,
            'value'     => 'second',
            'properties'=> 'a:1:{s:4:"list";a:2:{i:0;a:2:{s:5:"label";s:12:"First option";s:5:"value";s:5:"first";}i:1;a:2:{s:5:"label";s:13:"Second option";s:5:"value";s:6:"second";}}}',
        ];
        $this->assertEquals('Second option', CustomFieldValueHelper::normalizeValue($field));
    }

    public function testNormalizeValueMultiSelect()
    {
        $field = [
            'type'      => CustomFieldValueHelper::TYPE_MULTISELECT,
            'value'     => 'option 1',
            'properties'=> 'a:1:{s:4:"list";a:3:{i:0;a:2:{s:5:"label";s:12:"Option 1 yes";s:5:"value";s:8:"option 1";}i:1;a:2:{s:5:"label";s:12:"Option 2 yes";s:5:"value";s:8:"option 2";}i:2;a:2:{s:5:"label";s:12:"Option 3 yes";s:5:"value";s:8:"option 3";}}}',
        ];

        $this->assertEquals('Option 1 yes', CustomFieldValueHelper::normalizeValue($field));

        $field = [
            'type'      => CustomFieldValueHelper::TYPE_MULTISELECT,
            'value'     => 'option 4',
            'properties'=> 'a:1:{s:4:"list";a:3:{i:0;a:2:{s:5:"label";s:12:"Option 1 yes";s:5:"value";s:8:"option 1";}i:1;a:2:{s:5:"label";s:12:"Option 2 yes";s:5:"value";s:8:"option 2";}i:2;a:2:{s:5:"label";s:12:"Option 3 yes";s:5:"value";s:8:"option 3";}}}',
        ];

        $this->assertEquals('option 4', CustomFieldValueHelper::normalizeValue($field));
    }
}
