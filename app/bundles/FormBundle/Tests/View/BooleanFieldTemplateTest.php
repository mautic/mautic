<?php

namespace Mautic\FormBundle\Tests\View;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Twig\Environment;

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
            'no' => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field' => $field,
            'id' => 'test',
            'formId' => 1,
            'formName' => 'test_form',
        ]);

        // Debug: Let's see what the actual HTML contains
        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringContainsString('Custom No', $html);
        
        // Check if the values are correct (should be '0' and '1')
        if (strpos($html, 'value="0"') !== false && strpos($html, 'value="1"') !== false) {
            $this->assertTrue(true, 'Values are correctly set to boolean values');
        } else {
            // If not, let's see what values are actually there
            $this->assertStringContainsString('value="Custom Yes"', $html);
            $this->assertStringContainsString('value="Custom No"', $html);
            $this->fail('Values are still labels instead of boolean values. HTML: ' . $html);
        }
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
            'no' => '',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field' => $field,
            'id' => 'test',
            'formId' => 1,
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
            'no' => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field' => $field,
            'id' => 'test',
            'formId' => 1,
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
            'field' => $field,
            'id' => 'test',
            'formId' => 1,
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
            'no' => 'Custom No',
        ]);

        $html = $twig->render('@MauticForm/Field/boolean.html.twig', [
            'field' => $field,
            'id' => 'test',
            'formId' => 1,
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
} 