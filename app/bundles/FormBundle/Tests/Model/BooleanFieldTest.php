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

        // Test with boolean value '1'
        $result = $normalizeValueMethod->invoke($submissionModel, '1', $field);
        $this->assertEquals('1', $result);

        // Test with custom label that matches 'yes' property
        $result = $normalizeValueMethod->invoke($submissionModel, 'Custom Yes', $field);
        $this->assertEquals('1', $result);

        // Test with empty 'no' property - should still work with standard values
        $result = $normalizeValueMethod->invoke($submissionModel, '0', $field);
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
} 