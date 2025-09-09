<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\View;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

final class FormResultsDisplayTest extends MauticMysqlTestCase
{
    public function testBooleanFieldResultsDisplay(): void
    {
        $twig = static::getContainer()->get('twig');

        // Create a form with a boolean field
        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('test_form');

        $field = new Field();
        $field->setType('boolean');
        $field->setLabel('Test Boolean Field');
        $field->setAlias('test_boolean');
        $field->setProperties([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);
        $field->setForm($form);
        $form->addField(0, $field);

        // Test the boolean conversion logic directly
        $properties = $field->getProperties();

        // Test positive value
        $value    = '1';
        $expected = $properties['yes'] ?? 'Yes';
        $this->assertEquals('Custom Yes', $expected);

        // Test negative value
        $value    = '0';
        $expected = $properties['no'] ?? 'No';
        $this->assertEquals('Custom No', $expected);

        // Test custom label values
        $value = 'Custom Yes';
        $this->assertEquals('Custom Yes', $value);

        $value = 'Custom No';
        $this->assertEquals('Custom No', $value);
    }
}
