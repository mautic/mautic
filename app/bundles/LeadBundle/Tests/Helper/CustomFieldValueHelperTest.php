<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;

class CustomFieldValueHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testNormalizeValueBooleans()
    {
        $fieldParams = [
            'type'      => CustomFieldValueHelper::TYPE_BOOLEAN,
            'value'     => 1,
            'properties'=> 'a:2:{s:2:"no";s:2:"No";s:3:"yes";s:3:"Yes";}',
        ];

        $fields['core']['test'] = $fieldParams;

        $fieldParams['value']    = 0;
        $fields['core']['test2'] = $fieldParams;

        $fieldParams['value']    = null;
        $fields['core']['test3'] = $fieldParams;

        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);

        $this->assertEquals('Yes', $normalizedFields['core']['test']['normalizedValue']);
        $this->assertEquals('No', $normalizedFields['core']['test2']['normalizedValue']);
        $this->assertEquals('', $normalizedFields['core']['test3']['normalizedValue']);
    }

    public function testNormalizeValueSelect()
    {
        $fields['core']['test'] = [
            'type'      => CustomFieldValueHelper::TYPE_SELECT,
            'value'     => 'second',
            'properties'=> 'a:1:{s:4:"list";a:2:{i:0;a:2:{s:5:"label";s:12:"First option";s:5:"value";s:5:"first";}i:1;a:2:{s:5:"label";s:13:"Second option";s:5:"value";s:6:"second";}}}',
        ];
        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);
        $this->assertEquals('Second option', $normalizedFields['core']['test']['normalizedValue']);
    }

    public function testNormalizeValueSelectWithoutProperties()
    {
        $fields['core']['test'] = [
            'type'      => CustomFieldValueHelper::TYPE_SELECT,
            'value'     => 'second',
        ];
        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);
        $this->assertEquals('second', $normalizedFields['core']['test']['normalizedValue']);
    }

    public function testNormalizeValueMultiSelect()
    {
        $fieldParams = [
            'type'      => CustomFieldValueHelper::TYPE_MULTISELECT,
            'value'     => 'option 1',
            'properties'=> 'a:1:{s:4:"list";a:3:{i:0;a:2:{s:5:"label";s:12:"Option 1 yes";s:5:"value";s:8:"option 1";}i:1;a:2:{s:5:"label";s:12:"Option 2 yes";s:5:"value";s:8:"option 2";}i:2;a:2:{s:5:"label";s:12:"Option 3 yes";s:5:"value";s:8:"option 3";}}}',
        ];

        $fields['core']['test'] = $fieldParams;

        $fieldParams['value']    = 'option 4';
        $fields['core']['test2'] = $fieldParams;

        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);

        $this->assertEquals('Option 1 yes', $normalizedFields['core']['test']['normalizedValue']);
        $this->assertEquals('option 4', $normalizedFields['core']['test2']['normalizedValue']);
    }

    public function testAnonimizationFields()
    {
        $fields = [
            'core' => [
                'firstname' => [
                    'id'       => 2,
                    'label'    => 'First Name',
                    'alias'    => 'firstname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 'John',
                ],
                'lastname' => [
                    'id'       => 3,
                    'label'    => 'Last Name',
                    'alias'    => 'lastname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 'Doe',
                ],
                'email' => [
                    'id'              => 6,
                    'label'           => 'Email',
                    'alias'           => 'email',
                    'type'            => 'email',
                    'group'           => 'core',
                    'object'          => 'lead',
                    'is_fixed'        => '1',
                    'properties'      => 'a:0:{}',
                    'default_value'   => '',
                    'value'           => 'john+1023@mautic.org',
                    'normalizedValue' => 'john+1023@mautic.org',
                ],
            ],
            'professional' => [
                'userip' => [
                    'id'              => '55',
                    'label'           => 'User IP',
                    'alias'           => 'userip',
                    'type'            => 'text',
                    'group'           => 'professional',
                    'object'          => 'lead',
                    'is_fixed'        => '0',
                    'properties'      => 'a:0:{}',
                    'default_value'   => null,
                    'value'           => '161.3.189.71',
                    'normalizedValue' => '161.3.189.71',
                ],
            ],
        ];

        $expected = [
            'firstname' => '*',
            'lastname'  => '*',
            'userip'    => '*',
            'email'     => '*@mautic.org',
        ];

        $result = CustomFieldValueHelper::anonimizationFields($fields);
        $this->assertEquals($expected, $result);
    }
}
