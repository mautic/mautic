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

        // Should contain both options: "No" (default) with value="0" and "Custom Yes" with value="1"
        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringContainsString('No', $html);
        $this->assertStringContainsString('value="0"', $html);
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

        // Should contain both options: "Custom No" with value="0" and "Yes" (default) with value="1"
        $this->assertStringContainsString('Custom No', $html);
        $this->assertStringContainsString('Yes', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('value="1"', $html);
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
} 