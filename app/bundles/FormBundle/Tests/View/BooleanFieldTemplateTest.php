<?php

namespace Mautic\FormBundle\Tests\View;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;

class BooleanFieldTemplateTest extends MauticMysqlTestCase
{
    public function testBooleanFieldTemplateWithCustomLabels(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Debug: Let's see what the actual HTML contains
        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringContainsString('Custom No', $html);

        // Check if the values are correct (should be '0' and '1')
        $this->assertStringContainsString('value="0"', $html, 'Should contain value="0" for No option');
        $this->assertStringContainsString('value="1"', $html, 'Should contain value="1" for Yes option');
    }

    public function testBooleanFieldTemplateWithBlankLabels(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => '',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Should only contain the "Custom Yes" option since "no" label is blank
        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringNotContainsString('No', $html);
        $this->assertStringNotContainsString('value="0"', $html);
        $this->assertStringContainsString('value="1"', $html);
    }

    public function testBooleanFieldTemplateWithBlankYesLabel(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => '',
            'no'  => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Should only contain the "Custom No" option since "yes" label is blank
        $this->assertStringContainsString('Custom No', $html);
        $this->assertStringNotContainsString('Yes', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringNotContainsString('value="1"', $html);
    }

    public function testBooleanFieldTemplateWithDefaultLabels(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Should contain default labels with boolean values
        $this->assertStringContainsString('Yes', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('value="1"', $html);
    }

    public function testBooleanFieldTemplateNoDefaultSelection(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Should contain both options but no option should be pre-selected
        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringContainsString('Custom No', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('value="1"', $html);

        // Verify that no option is pre-selected (no "checked" attribute)
        $this->assertStringNotContainsString('checked="checked"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testBooleanFieldTemplateCssClasses(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Verify that the container has the boolean-specific class
        $this->assertStringContainsString('mauticform-boolean', $html);

        // Verify that the positive option has the positive class
        $this->assertStringContainsString('mauticform-boolean-positive', $html);

        // Verify that the negative option has the negative class
        $this->assertStringContainsString('mauticform-boolean-negative', $html);

        // Verify that both options have the base radio class
        $this->assertStringContainsString('mauticform-radiogrp-radio', $html);
    }

    public function testBooleanFieldTemplateCheckboxMode(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'I wanna receive comm',
            'no'  => '', // Empty negative label
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Verify that it renders as a checkbox (not radio)
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringNotContainsString('type="radio"', $html);

        // Verify that the label is present
        $this->assertStringContainsString('I wanna receive comm', $html);

        // Verify that only one option is rendered
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringNotContainsString('value="0"', $html);

        // Verify checkbox-specific classes
        $this->assertStringContainsString('mauticform-checkboxgrp-checkbox', $html);
    }

    public function testBooleanFieldTemplateCheckboxModeOnlyNoLabel(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => '', // Empty positive label
            'no'  => 'I do not want to receive comm',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Verify that it renders as a checkbox (not radio)
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringNotContainsString('type="radio"', $html);

        // Verify that the label is present
        $this->assertStringContainsString('I do not want to receive comm', $html);

        // Verify that only one option is rendered
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringNotContainsString('value="1"', $html);

        // Verify checkbox-specific classes
        $this->assertStringContainsString('mauticform-checkboxgrp-checkbox', $html);
    }

    public function testBooleanFieldTemplateCheckboxModeSubmissionSimulation(): void
    {
        $twig = static::getContainer()->get('twig');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'I wanna receive comm',
            'no'  => '', // Empty negative label
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field'    => $field,
            'id'       => 'test',
            'formId'   => 1,
            'formName' => 'test_form',
        ]);

        // Verify that it renders as a checkbox
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('I wanna receive comm', $html);

        // Verify that the name attribute includes [] for checkbox
        $this->assertStringContainsString('name="mauticform[test_boolean][]"', $html);

        // Verify checkbox-specific classes
        $this->assertStringContainsString('mauticform-checkboxgrp-checkbox', $html);
        $this->assertStringContainsString('mauticform-boolean-positive', $html);
    }
}
