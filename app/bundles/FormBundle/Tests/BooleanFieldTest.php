<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\SubmissionModel;
use Twig\Environment;

class BooleanFieldTest extends MauticMysqlTestCase
{
    protected SubmissionModel $submissionModel;
    protected \ReflectionMethod $normalizeValueMethod;
    protected Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->submissionModel      = static::getContainer()->get('mautic.form.model.submission');
        $reflection                 = new \ReflectionClass($this->submissionModel);
        $this->normalizeValueMethod = $reflection->getMethod('normalizeValue');
        $this->normalizeValueMethod->setAccessible(true);
        $this->twig = static::getContainer()->get('twig');
    }

    /**
     * @param array<string, string> $properties
     */
    protected function createBooleanField(array $properties = []): Field
    {
        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties($properties);

        return $field;
    }

    protected function assertNormalizedValue(mixed $input, Field $field, bool $expected): void
    {
        $result = $this->normalizeValueMethod->invoke($this->submissionModel, $input, $field);
        $this->assertEquals($expected, $result);
    }

    protected function renderBooleanField(Field $field): string
    {
        return $this->twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);
    }

    /**
     * @param array<string> $expectedContent
     * @param array<string> $unexpectedContent
     */
    protected function assertBooleanFieldRendersCorrectly(Field $field, array $expectedContent, array $unexpectedContent = []): void
    {
        $html = $this->renderBooleanField($field);

        foreach ($expectedContent as $content) {
            $this->assertStringContainsString($content, $html);
        }

        foreach ($unexpectedContent as $content) {
            $this->assertStringNotContainsString($content, $html);
        }
    }

    public function testBooleanFieldFormSubmission(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $this->assertNormalizedValue('1', $field, true);
        $this->assertNormalizedValue('0', $field, true);
        $this->assertNormalizedValue('Custom Yes', $field, true);
        $this->assertNormalizedValue('Custom No', $field, true);
        $this->assertNormalizedValue('', $field, false);
        $this->assertNormalizedValue(null, $field, false);
    }

    public function testBooleanFieldWithBlankLabels(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => '',
        ]);

        $this->assertNormalizedValue(['1'], $field, true);
        $this->assertNormalizedValue('1', $field, true);
        $this->assertNormalizedValue('Custom Yes', $field, true);
        $this->assertNormalizedValue([''], $field, false);
        $this->assertNormalizedValue([], $field, false);
        $this->assertNormalizedValue('', $field, false);
        $this->assertNormalizedValue(null, $field, false);
    }

    public function testBooleanFieldValueNormalization(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $this->assertNormalizedValue('1', $field, true);
        $this->assertNormalizedValue('0', $field, true);
        $this->assertNormalizedValue('Custom Yes', $field, true);
        $this->assertNormalizedValue('Custom No', $field, true);
        $this->assertNormalizedValue('true', $field, true);
        $this->assertNormalizedValue('false', $field, true);
        $this->assertNormalizedValue('', $field, false);
        $this->assertNormalizedValue(null, $field, false);
    }

    public function testBooleanFieldWithDefaultLabels(): void
    {
        $field = $this->createBooleanField();

        $this->assertNormalizedValue('1', $field, true);
        $this->assertNormalizedValue('0', $field, true);
        $this->assertNormalizedValue('true', $field, true);
        $this->assertNormalizedValue('false', $field, true);
        $this->assertNormalizedValue('', $field, false);
        $this->assertNormalizedValue(null, $field, false);
    }

    public function testNormalizeValueWithEmptySubmission(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $this->assertNormalizedValue('', $field, false);
        $this->assertNormalizedValue(null, $field, false);
    }

    public function testNormalizeValueCheckboxModeOnlyYesLabel(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'I wanna receive comm',
            'no'  => '',
        ]);

        $this->assertNormalizedValue(['1'], $field, true);
        $this->assertNormalizedValue([''], $field, false);
        $this->assertNormalizedValue([], $field, false);
    }

    public function testNormalizeValueCheckboxModeOnlyNoLabel(): void
    {
        $field = $this->createBooleanField([
            'yes' => '',
            'no'  => 'I do not want to receive comm',
        ]);

        $this->assertNormalizedValue(['0'], $field, true);
        $this->assertNormalizedValue([''], $field, false);
        $this->assertNormalizedValue([], $field, false);
    }

    public function testBooleanFieldTemplateWithCustomLabels(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'Custom Yes',
            'Custom No',
            'value="0"',
            'value="1"',
        ]);
    }

    public function testBooleanFieldTemplateWithBlankLabels(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => '',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'Custom Yes',
            'value="1"',
        ], [
            'No',
            'value="0"',
        ]);
    }

    public function testBooleanFieldTemplateWithBlankYesLabel(): void
    {
        $field = $this->createBooleanField([
            'yes' => '',
            'no'  => 'Custom No',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'Custom No',
            'value="0"',
        ], [
            'Yes',
            'value="1"',
        ]);
    }

    public function testBooleanFieldTemplateWithDefaultLabels(): void
    {
        $field = $this->createBooleanField();

        $this->assertBooleanFieldRendersCorrectly($field, [
            'Yes',
            'No',
            'value="0"',
            'value="1"',
        ]);
    }

    public function testBooleanFieldTemplateNoDefaultSelection(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $html = $this->renderBooleanField($field);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'Custom Yes',
            'Custom No',
            'value="0"',
            'value="1"',
        ]);

        $this->assertStringNotContainsString('checked="checked"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testBooleanFieldTemplateCssClasses(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'mauticform-boolean',
            'mauticform-boolean-positive',
            'mauticform-boolean-negative',
            'mauticform-radiogrp-radio',
        ]);
    }

    public function testBooleanFieldTemplateCheckboxMode(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'I wanna receive comm',
            'no'  => '',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'type="checkbox"',
            'I wanna receive comm',
            'value="1"',
            'mauticform-checkboxgrp-checkbox',
        ], [
            'type="radio"',
            'value="0"',
        ]);
    }

    public function testBooleanFieldTemplateCheckboxModeOnlyNoLabel(): void
    {
        $field = $this->createBooleanField([
            'yes' => '',
            'no'  => 'I do not want to receive comm',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'type="checkbox"',
            'I do not want to receive comm',
            'value="0"',
            'mauticform-checkboxgrp-checkbox',
        ], [
            'type="radio"',
            'value="1"',
        ]);
    }

    public function testBooleanFieldTemplateCheckboxModeSubmissionSimulation(): void
    {
        $field = $this->createBooleanField([
            'yes' => 'I wanna receive comm',
            'no'  => '',
        ]);

        $this->assertBooleanFieldRendersCorrectly($field, [
            'type="checkbox"',
            'value="1"',
            'I wanna receive comm',
            'name="mauticform[test_boolean][]"',
            'mauticform-checkboxgrp-checkbox',
            'mauticform-boolean-positive',
        ]);
    }
}
