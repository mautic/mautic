<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Twig;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;

class FieldTemplateTest extends MauticMysqlTestCase
{
    public function testFieldTemplateRendersWithCssClasses(): void
    {
        $field = new Field();
        $field->setType('text');
        $field->setLabel('Test Field');
        $field->setAlias('test_field');
        $field->setFieldWidth('50%');
        $field->setOrder(1);

        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('test_form');

        $twig     = $this->getContainer()->get('twig');
        $template = $twig->load('@MauticForm/Field/text.html.twig');

        $html = $template->render([
            'field'          => $field,
            'fields'         => [],
            'id'             => 'test_id',
            'formName'       => 'test_form',
            'containerClass' => 'text',
            'type'           => 'text',
            'inputClass'     => 'input',
        ]);

        $this->assertStringContainsString('mauticform-half-width', $html);
        $this->assertStringNotContainsString('style="width: 50%"', $html);
    }

    public function testFieldTemplateRendersFullWidthByDefault(): void
    {
        $field = new Field();
        $field->setType('text');
        $field->setLabel('Test Field');
        $field->setAlias('test_field');
        $field->setOrder(1);

        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('test_form');

        $twig     = $this->getContainer()->get('twig');
        $template = $twig->load('@MauticForm/Field/text.html.twig');

        $html = $template->render([
            'field'          => $field,
            'fields'         => [],
            'id'             => 'test_id',
            'formName'       => 'test_form',
            'containerClass' => 'text',
            'type'           => 'text',
            'inputClass'     => 'input',
        ]);

        $this->assertStringContainsString('mauticform-full-width', $html);
    }

    public function testFieldTemplateMapsAllWidthValuesCorrectly(): void
    {
        $widthMappings = [
            '100%'   => 'mauticform-full-width',
            '75%'    => 'mauticform-three-quarters-width',
            '66.66%' => 'mauticform-two-thirds-width',
            '50%'    => 'mauticform-half-width',
            '33.33%' => 'mauticform-one-third-width',
            '25%'    => 'mauticform-one-quarter-width',
        ];

        foreach ($widthMappings as $percentage => $expectedClass) {
            $field = new Field();
            $field->setType('text');
            $field->setLabel('Test Field');
            $field->setAlias('test_field');
            $field->setFieldWidth($percentage);
            $field->setOrder(1);

            $twig     = $this->getContainer()->get('twig');
            $template = $twig->load('@MauticForm/Field/text.html.twig');

            $html = $template->render([
                'field'          => $field,
                'fields'         => [],
                'id'             => 'test_id',
                'formName'       => 'test_form',
                'containerClass' => 'text',
                'type'           => 'text',
                'inputClass'     => 'input',
            ]);

            $this->assertStringContainsString($expectedClass, $html, "Field width {$percentage} should map to class {$expectedClass}");
            $this->assertStringNotContainsString("style=\"width: {$percentage}\"", $html, "Field width {$percentage} should not have inline style");
        }
    }
}
