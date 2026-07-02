<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class FormFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFieldHelper
     */
    protected $fixture;

    protected function setUp(): void
    {
        $translatorMock = $this->createMock(Translator::class);

        $validatorMock = $this->createMock(ValidatorInterface::class);

        $this->fixture = new FormFieldHelper($translatorMock, $validatorMock);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fieldProvider')]
    public function testPopulateField(Field $field, mixed $value, string &$formHtml, mixed $expectedValue, string $message): void
    {
        $this->fixture->populateField($field, $value, 'mautic', $formHtml);

        $this->assertEquals($expectedValue, $formHtml, $message);
    }

    /** @return array<int, array{0: Field, 1: mixed, 2: string, 3: mixed, 4: string}> */
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
            [
                self::getField('Rating', 'rating'),
                '3',
                '<input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'1" value="1"/><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'2" value="2"/><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'3" value="3"/>',
                '<input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'1" value="1"/><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'2" value="2"/><input id="mauticform_radiogrp_radio_'.self::getAliasFromName('Rating').'3" value="3" checked />',
                'Rating fields should have their values set appropriately via GET.',
            ],
        ];
    }

    public function testRatingFieldBuildsConfiguredStarList(): void
    {
        $field = self::getField('Rating', 'rating');
        $field->setProperties(['star_count' => 6]);

        $this->assertSame([
            1 => '★',
            2 => '★',
            3 => '★',
            4 => '★',
            5 => '★',
            6 => '★',
        ], self::getRatingList($field));
    }

    public function testRatingListIsParsedForTemplateChoices(): void
    {
        $field = self::getField('Rating', 'rating');
        $field->setProperties(['star_count' => 6]);

        $this->assertSame(self::getRatingList($field), \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList(self::getRatingList($field)));
    }

    public function testRatingTemplateUsesDescendingRadioValues(): void
    {
        $field = self::getField('Rating', 'rating');
        $field->setProperties(['star_count' => 6]);

        $this->assertSame([6, 5, 4, 3, 2, 1], range($field->getProperties()['star_count'], 1, -1));
    }

    /**
     * @return array<int, string>
     */
    private static function getRatingList(Field $field): array
    {
        $max  = $field->getProperties()['star_count'] ?? 5;
        $list = [];
        for ($i = 1; $i <= $max; ++$i) {
            $list[$i] = '★';
        }

        return $list;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('selectAutoFillProvider')]
    public function testPopulateFieldSelectAutoFill(string $type, string $value, string $options, string $expectedOptions, string $message): void
    {
        $open = '<select name="mauticform['.$type.']" id="mauticform_input_mautic_'.$type.'" class="form-control">';
        $html = $open.$options.'</select>';

        $this->fixture->populateField(self::getField(ucfirst($type), $type), $value, 'mautic', $html);

        $this->assertSame($open.$expectedOptions.'</select>', $html, $message);
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function selectAutoFillProvider(): iterable
    {
        yield 'attributes precede the id attribute' => [
            'select',
            'myvalue',
            '<option value="myvalue">My Value</option>',
            '<option value="myvalue" selected="selected">My Value</option>',
            'Select lists should be auto-filled even when other attributes precede the id attribute.',
        ];

        yield 'whitespace precedes the option closing bracket' => [
            'select',
            'myvalue',
            '<option value="myvalue" >My Value</option>',
            '<option value="myvalue" selected="selected">My Value</option>',
            'Select options should be auto-filled even when whitespace precedes the closing bracket.',
        ];

        yield 'only the matching option is selected' => [
            'country',
            'myvalue',
            '<option value="other">Other</option><option value="myvalue" >My Value</option>',
            '<option value="other">Other</option><option value="myvalue" selected="selected">My Value</option>',
            'Country lists should auto-fill only the option matching the submitted value.',
        ];

        yield 'regex backreference characters preserved verbatim' => [
            'select',
            '$1promo',
            '<option value="$1promo">Promo</option>',
            '<option value="$1promo" selected="selected">Promo</option>',
            'Option values containing regex backreference characters should be preserved verbatim when marked selected.',
        ];
    }

    /**
     * @param string $name
     * @param string $type
     */
    protected static function getField($name, $type): Field
    {
        $field = new Field();

        $field->setLabel($name);
        $field->setAlias(self::getAliasFromName($name));
        $field->setType($type);

        return $field;
    }

    /**
     * @param string $name
     */
    private static function getAliasFromName($name): string
    {
        return strtolower(str_replace(' ', '', $name));
    }
}
