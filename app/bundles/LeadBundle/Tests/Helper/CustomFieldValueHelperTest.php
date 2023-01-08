<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\CustomFieldValueHelper;
use PHPUnit\Framework\Assert;

class CustomFieldValueHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<int|string> $fieldParams
     */
    private function runNormalizeValueBooleans(array $fieldParams): void
    {
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

    public function testNormalizeValueBooleans(): void
    {
        $fieldParams = [
            'type'      => CustomFieldValueHelper::TYPE_BOOLEAN,
            'value'     => 1,
            'properties'=> 'a:2:{s:2:"no";s:2:"No";s:3:"yes";s:3:"Yes";}',
        ];

        $this->runNormalizeValueBooleans($fieldParams);
    }

    public function testNormalizeValueBooleansWithDifferentProperties(): void
    {
        $fieldParams = [
            'type'      => CustomFieldValueHelper::TYPE_BOOLEAN,
            'value'     => 1,
            'properties'=> 'a:2:{s:3:"yes";s:3:"Yes";s:2:"no";s:2:"No";}',
        ];

        $this->runNormalizeValueBooleans($fieldParams);
    }

    public function testNormalizeValueSelect(): void
    {
        $fields['core']['test'] = [
            'type'      => CustomFieldValueHelper::TYPE_SELECT,
            'value'     => 'second',
            'properties'=> 'a:1:{s:4:"list";a:2:{i:0;a:2:{s:5:"label";s:12:"First option";s:5:"value";s:5:"first";}i:1;a:2:{s:5:"label";s:13:"Second option";s:5:"value";s:6:"second";}}}',
        ];
        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);
        $this->assertEquals('Second option', $normalizedFields['core']['test']['normalizedValue']);
    }

    public function testNormalizeValueSelectWithoutProperties(): void
    {
        $fields['core']['test'] = [
            'type'      => CustomFieldValueHelper::TYPE_SELECT,
            'value'     => 'second',
        ];
        $normalizedFields = CustomFieldValueHelper::normalizeValues($fields);
        $this->assertEquals('second', $normalizedFields['core']['test']['normalizedValue']);
    }

    public function testNormalizeValueMultiSelect(): void
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

    public function testSetValueFromPropertiesListWithoutList(): void
    {
        Assert::assertSame(
            'value_1',
            CustomFieldValueHelper::setValueFromPropertiesList([], 'value_1')
        );
    }

    public function testSetValueFromPropertiesListWithStringList(): void
    {
        Assert::assertSame(
            'value_1',
            CustomFieldValueHelper::setValueFromPropertiesList(['list' => 'some|string'], 'value_1')
        );
    }

    public function testSetValueFromPropertiesListWithAssociativeArrayList(): void
    {
        Assert::assertSame(
            'value_1',
            CustomFieldValueHelper::setValueFromPropertiesList(
                ['list' => ['value_1' => 'Label 1']],
                'value_1'
            )
        );
    }

    public function testSetValueFromPropertiesListWithArrayList(): void
    {
        Assert::assertSame(
            'Label 1',
            CustomFieldValueHelper::setValueFromPropertiesList(
                [
                    'list' => [
                        ['value' => 'value_1', 'label' => 'Label 1'],
                    ],
                ],
                'value_1'
            )
        );
    }
}
