<?php

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFieldHelper
     */
    protected $fixture;

    protected function setUp(): void
    {
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validatorMock = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fixture = new FormFieldHelper($translatorMock, $validatorMock);
    }

    /**
     * @dataProvider validFieldDataProvider
     */
    public function testPopulateDateTimeValues(string $type, string $defaultValue, string $expected, string $msg): void
    {
        $form = new Form();
        $form->setName('mautic');

        $field = new Field();
        $field->setType($type);
        $field->setDefaultValue($defaultValue);
        $field->setAlias('fieldalias');
        $form->addField(1, $field);

        $fieldHtml = "<input value=\"{$field->getDefaultValue()}\" id=\"mauticform_input_mautic_fieldalias\">";

        $actual = $this->fixture->populateDateTimeValues($form, $fieldHtml);

        $this->assertEquals($expected, $actual, $msg);
    }

    /**
     * @return array<array<string,string>>
     */
    public function validFieldDataProvider(): array
    {
        return [
            [
                'type'         => 'date',
                'defaultValue' => 'now',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="'.(new \DateTime())->format('Y-m-d').'"/>',
                'msg'          => '`now` should be parsed into the current date format',
            ],
            [
                'type'         => 'datetime',
                'defaultValue' => '2024-09-09 +2 days 6am',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="2024-09-11T06:00"/>',
                'msg'          => '`2024-09-09 +2 days 6am` should be parsed into the correct date time format',
            ],
            [
                'type'         => 'hidden',
                'defaultValue' => 'may 4th 1977|date',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="1977-05-04T00:00"/>',
                'msg'          => 'hidden field value with `|date` should be parsed into the correct date time format',
            ],
        ];
    }

    /**
     * @dataProvider invalidFieldDataProvider
     */
    public function testSkippingPopulateDateTimeValues(string $type, string $defaultValue, string $expected, string $msg): void
    {
        $form = new Form();
        $form->setName('mautic');

        $field = new Field();
        $field->setType($type);
        $field->setDefaultValue($defaultValue);
        $field->setAlias('fieldalias');
        $form->addField(1, $field);

        $fieldHtml = "<input id=\"mauticform_input_mautic_fieldalias\" value=\"{$field->getDefaultValue()}\"/>";

        $actual = $this->fixture->populateDateTimeValues($form, $fieldHtml);

        $this->assertEquals($expected, $actual, $msg);
    }

    /**
     * @return array<array<string,string>>
     */
    public function invalidFieldDataProvider(): array
    {
        return [
            [
                'type'         => 'date',
                'defaultValue' => 'wrongFormat',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="wrongFormat"/>',
                'msg'          => 'Arbitrary strings should not get parsed in date fields',
            ],
            [
                'type'         => 'date',
                'defaultValue' => '',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value=""/>',
                'msg'          => 'Empty strings should not get parsed in date fields',
            ],
            [
                'type'         => 'hidden',
                'defaultValue' => '',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value=""/>',
                'msg'          => 'Empty strings should not get parsed in hidden fields',
            ],
            [
                'type'         => 'hidden',
                'defaultValue' => 'someHiddenValue',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="someHiddenValue"/>',
                'msg'          => 'Arbitrary strings should not get parsed in hidden fields',
            ],
            [
                'type'         => 'hidden',
                'defaultValue' => 'now',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="now"/>',
                'msg'          => 'Valid date strings should not get parsed in hidden fields without `|date` present',
            ],
            [
                'type'         => 'hidden',
                'defaultValue' => '|date',
                'expected'     => '<input id="mauticform_input_mautic_fieldalias" value="|date"/>',
                'msg'          => 'Empty date strings should not get parsed in hidden fields, even if `|date` is present',
            ],
        ];
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testPopulateField($field, $value, $formHtml, $expectedValue, $message): void
    {
        $this->fixture->populateField($field, $value, 'mautic', $formHtml);

        $this->assertEquals($expectedValue, $formHtml, $message);
    }

    /**
     * @return array<array<int,string>>
     */
    public static function fieldProvider(): array
    {
        return [
            [
                self::getField('First Name', 'text'),
                '%22%2F%3E%3Cscript%3Ealert%280%29%3C%2Fscript%3E',
                '<input value="" id="mauticform_input_mautic_firstname" />',
                '<input id="mauticform_input_mautic_firstname" value="&quot;/&gt;alert(0)" />',
                'Tags should be stripped from textet field values submitted via GET to prevent XSS.',
            ],
            [
                self::getField('First Name', 'text'),
                '%22%20onfocus=%22alert(123)',
                '<input value="" id="mauticform_input_mautic_firstname" />',
                '<input id="mauticform_input_mautic_firstname" value="&quot; onfocus=&quot;alert(123)" />',
                'Inline JS values should not be allowed via GET to prevent XSS.',
            ],
            [
                self::getField('Phone', 'tel'),
                '+41 123 456 7890',
                '<input value="" id="mauticform_input_mautic_phone" />',
                '<input id="mauticform_input_mautic_phone" value="+41 123 456 7890" />',
                'Phone number are populated properly',
            ],
            [
                self::getField('Description', 'textarea'),
                '%22%2F%3E%3Cscript%3Ealert%280%29%3C%2Fscript%3E',
                '<textarea id="mauticform_input_mautic_description"></textarea>',
                '<textarea id="mauticform_input_mautic_description">&quot;/&gt;alert(0)</textarea>',
                'Tags should be stripped from textarea field values submitted via GET to prevent XSS.',
            ],
            [
                self::getField('Description', 'textarea'),
                '%22%20onfocus=%22alert(123)',
                '<textarea id="mauticform_input_mautic_description"></textarea>',
                '<textarea id="mauticform_input_mautic_description">&quot; onfocus=&quot;alert(123)</textarea>',
                'Tags should be stripped from textarea field values submitted via GET to prevent XSS.',
            ],
            [
                self::getField('Checkbox Single', 'checkboxgrp'),
                'myvalue',
                '<input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Single').'1" value="myvalue"/><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Single').'2" value="notmyvalue"/>',
                '<input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Single').'1" value="myvalue" checked /><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Single').'2" value="notmyvalue"/>',
                'Single value checkbox groups should have their values set appropriately via GET.',
            ],
            [
                self::getField('Checkbox Multi', 'checkboxgrp'),
                'myvalue%7Calsomyvalue',
                '<input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'1" value="myvalue"/><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'2" value="alsomyvalue"/><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'3" value="notmyvalue"/>',
                '<input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'1" value="myvalue" checked /><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'2" value="alsomyvalue" checked /><input id="mauticform_checkboxgrp_checkbox_'.self::getAliasFromName('Checkbox Multi').'3" value="notmyvalue"/>',
                'Multi-value checkbox groups should have their values set appropriately via GET.',
            ],
            [
                self::getField('Radio Single', 'radiogrp'),
                'myvalue',
                '<input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Radio Single').'1" value="myvalue"/><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Radio Single').'1" value="notmyvalue"/>',
                '<input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Radio Single').'1" value="myvalue" checked /><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Radio Single').'1" value="notmyvalue"/>',
                'Single value radio groups should have their values set appropriately via GET.',
            ],
            [
                self::getField('Select', 'select'),
                'myvalue',
                '<select id="mauticform_input_mautic_select"><option value="myvalue">My Value</option></select>',
                '<select id="mauticform_input_mautic_select"><option value="myvalue" selected="selected">My Value</option></select>',
                'Select lists should have their values set appropriately via GET.',
            ],
        ];
    }

    protected static function getField(string $name, string $type): Field
    {
        $field = new Field();

        $field->setLabel($name);
        $field->setAlias(self::getAliasFromName($name));
        $field->setType($type);

        return $field;
    }

    private static function getAliasFromName(string $name): string
    {
        return strtolower(str_replace(' ', '', $name));
    }
}
