<?php

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormFieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFieldHelper
     */
    protected $fixture;

    public function setUp()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validatorMock = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fixture = new FormFieldHelper($translatorMock, $validatorMock);
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testPopulateField($field, $value, $formHtml, $expectedValue, $message)
    {
        $this->fixture->populateField($field, $value, 'mautic', $formHtml);

        $this->assertEquals($expectedValue, $formHtml, $message);
    }

    public function fieldProvider()
    {
        return [
            [
                $this->getField('First Name', 'text'),
                '%22%2F%3E%3Cscript%3Ealert%280%29%3C%2Fscript%3E',
                '<input id="mauticform_input_mautic_firstname" value="" />',
                '<input id="mauticform_input_mautic_firstname" value=""/>alert(0)" />',
                'Tags should be stripped from text field values submitted via GET to prevent XSS.',
            ],
            [
                $this->getField('Description', 'textarea'),
                '%22%2F%3E%3Cscript%3Ealert%280%29%3C%2Fscript%3E',
                '<textarea id="mauticform_input_mautic_description"></textarea>',
                '<textarea id="mauticform_input_mautic_description">"/>alert(0)</textarea>',
                'Tags should be stripped from textarea field values submitted via GET to prevent XSS.',
            ],
            [
                $this->getField('Checkbox Single', 'checkboxgrp'),
                'myvalue',
                '<input id="mauticform_checkboxgrp_checkbox1" value="myvalue"/><input id="mauticform_checkboxgrp_checkbox2" value="notmyvalue"/>',
                '<input id="mauticform_checkboxgrp_checkbox1" value="myvalue" checked /><input id="mauticform_checkboxgrp_checkbox2" value="notmyvalue"/>',
                'Single value checkbox groups should have their values set appropriately via GET.',
            ],
            [
                $this->getField('Checkbox Multi', 'checkboxgrp'),
                'myvalue|alsomyvalue',
                '<input id="mauticform_checkboxgrp_checkbox1" value="myvalue"/><input id="mauticform_checkboxgrp_checkbox2" value="alsomyvalue"/><input id="mauticform_checkboxgrp_checkbox3" value="notmyvalue"/>',
                '<input id="mauticform_checkboxgrp_checkbox1" value="myvalue" checked /><input id="mauticform_checkboxgrp_checkbox2" value="alsomyvalue" checked /><input id="mauticform_checkboxgrp_checkbox3" value="notmyvalue"/>',
                'Multi-value checkbox groups should have their values set appropriately via GET.',
            ],
            [
                $this->getField('Radio Single', 'radiogrp'),
                'myvalue',
                '<input id="mauticform_radiogrp_radio1" value="myvalue"/><input id="mauticform_radiogrp_radio1" value="notmyvalue"/>',
                '<input id="mauticform_radiogrp_radio1" value="myvalue" checked /><input id="mauticform_radiogrp_radio1" value="notmyvalue"/>',
                'Single value radio groups should have their values set appropriately via GET.',
            ],
            [
                $this->getField('Select', 'select'),
                'myvalue',
                '<select id="mauticform_input_mautic_select"><option value="myvalue">My Value</option></select>',
                '<select id="mauticform_input_mautic_select"><option value="myvalue" selected="selected">My Value</option></select>',
                'Select lists should have their values set appropriately via GET.',
            ],
        ];
    }

    protected function getField($name, $type)
    {
        $field = new Field();

        $field->setLabel($name);
        $field->setAlias(strtolower(str_replace(' ', '', $name)));
        $field->setType($type);

        return $field;
    }
}
