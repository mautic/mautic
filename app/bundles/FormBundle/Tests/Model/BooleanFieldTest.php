<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\SubmissionModel;
use ReflectionClass;

class BooleanFieldTest extends MauticMysqlTestCase
{
    public function testBooleanFieldValueNormalization(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no' => 'Custom No',
        ]);

        // Test with boolean value '1'
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        // Test with boolean value '0'
        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
        $this->assertEquals('0', $result);

        // Test with custom label that matches 'yes' property
        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals('1', $result);

        // Test with custom label that matches 'no' property
        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom No', $field);
        $this->assertEquals('0', $result);

        // Test with standard boolean values
        $result = $normalizeValueMethod->invoke($submissionModel, 'true', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'false', $field);
        $this->assertEquals('0', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'yes', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'no', $field);
        $this->assertEquals('0', $result);
    }

    public function testBooleanFieldWithBlankLabels(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no' => '',
        ]);

        // This field has only yes label, so it's in checkbox mode
        // Test with boolean value '1' (checked)
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        // Test with custom label that matches 'yes' property (checked)
        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals('1', $result);

        // Test with empty value (unchecked checkbox)
        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals('0', $result);

        // Test with null value (unchecked checkbox)
        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals('0', $result);
    }

    public function testBooleanFieldWithDefaultLabels(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([]);

        // Test with boolean values when no custom properties are set
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
        $this->assertEquals('0', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'true', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'false', $field);
        $this->assertEquals('0', $result);
    }

    public function testNormalizeValueWithEmptySubmission(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no' => 'Custom No',
        ]);

        // Test empty submission
        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals('', $result);

        // Test null submission
        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals('', $result);
    }

    public function testNormalizeValueCheckboxModeOnlyYesLabel(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'I wanna receive comm',
            'no' => '', // Empty negative label
        ]);

        // Test checkbox checked (submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, ['1'], $field);
        $this->assertEquals('1', $result);

        // Test checkbox unchecked (not submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, [''], $field);
        $this->assertEquals('0', $result);

        // Test checkbox unchecked (empty array)
        $result = $normalizeValueMethod->invoke($submissionModel, [], $field);
        $this->assertEquals('0', $result);
    }

    public function testNormalizeValueCheckboxModeOnlyNoLabel(): void
    {
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $reflection = new ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => '', // Empty positive label
            'no' => 'I do not want to receive comm',
        ]);

        // Test checkbox checked (submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, ['0'], $field);
        $this->assertEquals('0', $result);

        // Test checkbox unchecked (not submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, [''], $field);
        $this->assertEquals('1', $result);

        // Test checkbox unchecked (empty array)
        $result = $normalizeValueMethod->invoke($submissionModel, [], $field);
        $this->assertEquals('1', $result);
    }
} 