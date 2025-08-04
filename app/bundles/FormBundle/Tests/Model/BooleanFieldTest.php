<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;

class BooleanFieldTest extends MauticMysqlTestCase
{
    public function testBooleanFieldValueNormalization(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        // Radio mode (both labels) - should return original values without conversion
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
        $this->assertEquals('0', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals('Custom Yes', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom No', $field);
        $this->assertEquals('Custom No', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'true', $field);
        $this->assertEquals('true', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'false', $field);
        $this->assertEquals('false', $result);
    }

    public function testBooleanFieldWithBlankLabels(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => '',
        ]);

        // This field has only yes label, so it's in checkbox mode
        // Test with boolean value '1' (checked)
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals(true, $result);

        // Test with custom label that matches 'yes' property (checked)
        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals(true, $result);

        // Test with empty value (unchecked checkbox)
        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals(false, $result);

        // Test with null value (unchecked checkbox)
        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals(false, $result);
    }

    public function testBooleanFieldWithDefaultLabels(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([]);

        // No custom properties means radio mode - should return original values
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
        $this->assertEquals('0', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'true', $field);
        $this->assertEquals('true', $result);

        $result = $normalizeValueMethod->invoke($submissionModel, 'false', $field);
        $this->assertEquals('false', $result);
    }

    public function testNormalizeValueWithEmptySubmission(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        // Radio mode - empty submission should return empty string
        $result = $normalizeValueMethod->invoke($submissionModel, '', $field);
        $this->assertEquals('', $result);

        // Radio mode - null submission should return empty string
        $result = $normalizeValueMethod->invoke($submissionModel, null, $field);
        $this->assertEquals('', $result);
    }

    public function testNormalizeValueCheckboxModeOnlyYesLabel(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => 'I wanna receive comm',
            'no'  => '', // Empty negative label
        ]);

        // Test checkbox checked (submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, ['1'], $field);
        $this->assertEquals(true, $result);

        // Test checkbox unchecked (not submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, [''], $field);
        $this->assertEquals(false, $result);

        // Test checkbox unchecked (empty array)
        $result = $normalizeValueMethod->invoke($submissionModel, [], $field);
        $this->assertEquals(false, $result);
    }

    public function testNormalizeValueCheckboxModeOnlyNoLabel(): void
    {
        $submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection           = new \ReflectionClass($submissionModel);
        $normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $normalizeValueMethod->setAccessible(true);

        $field = new Field();
        $field->setType('boolean');
        $field->setProperties([
            'yes' => '', // Empty positive label
            'no'  => 'I do not want to receive comm',
        ]);

        // Test checkbox checked (submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, ['0'], $field);
        $this->assertEquals(true, $result);

        // Test checkbox unchecked (not submitted)
        $result = $normalizeValueMethod->invoke($submissionModel, [''], $field);
        $this->assertEquals(false, $result);

        // Test checkbox unchecked (empty array)
        $result = $normalizeValueMethod->invoke($submissionModel, [], $field);
        $this->assertEquals(false, $result);
    }
}
