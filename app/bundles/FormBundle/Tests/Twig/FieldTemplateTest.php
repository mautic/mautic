<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Twig;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

#[Group('database')]
final class FieldTemplateTest extends MauticMysqlTestCase
{
    private const string FIELD_LABEL         = 'Test Field';

    private const string FULL_WIDTH_CLASS    = 'mauticform-100';

    private const string TEXT_FIELD_TEMPLATE = '@MauticForm/Field/text.html.twig';

    private const string RADIO_GROUP_FIELD_TEMPLATE = '@MauticForm/Field/radiogrp.html.twig';

    public function testFieldTemplateRendersWithCssClasses(): void
    {
        $html = $this->renderTextField($this->createField('50%'));

        $this->assertStringContainsString('mauticform-50', $html);
        $this->assertStringNotContainsString('style="width: 50%"', $html);
    }

    public function testFieldTemplateRendersFullWidthByDefault(): void
    {
        $html = $this->renderTextField($this->createField());

        $this->assertStringContainsString(self::FULL_WIDTH_CLASS, $html);
    }

    public function testFieldTemplateMapsAllWidthValuesCorrectly(): void
    {
        $widthMappings = [
            '100%'   => self::FULL_WIDTH_CLASS,
            '75%'    => 'mauticform-75',
            '66.66%' => 'mauticform-66',
            '50%'    => 'mauticform-50',
            '33.33%' => 'mauticform-33',
            '25%'    => 'mauticform-25',
        ];

        foreach ($widthMappings as $percentage => $expectedClass) {
            $html = $this->renderTextField($this->createField($percentage));

            $this->assertStringContainsString($expectedClass, $html, "Field width {$percentage} should map to class {$expectedClass}");
            $this->assertStringNotContainsString("style=\"width: {$percentage}\"", $html, "Field width {$percentage} should not have inline style");
        }
    }

    public function testFieldTemplateDefaultsEmptyWidthToFullWidth(): void
    {
        $html = $this->renderTextField($this->createField(''));

        $this->assertStringContainsString(self::FULL_WIDTH_CLASS, $html);
        $this->assertStringNotContainsString('style="width:', $html);
    }

    public function testFieldTemplateDoesNotRenderDecimalClassSuffix(): void
    {
        $html = $this->renderTextField($this->createField('33.33%'));

        $this->assertStringContainsString('mauticform-33', $html);
        $this->assertStringNotContainsString('mauticform-33.33', $html);
    }

    public function testFieldTemplateKeepsCustomContainerAttributesWithWidthClass(): void
    {
        $field = $this->createField('25%');
        $field->setContainerAttributes('class="custom-row" data-test="custom-attr"');

        $html = $this->renderTextField($field);

        $this->assertStringContainsString('custom-row', $html);
        $this->assertStringContainsString('data-test="custom-attr"', $html);
        $this->assertStringContainsString('mauticform-25', $html);
    }

    public function testRadioGroupDoesNotSelectZeroOptionWithoutDefaultValue(): void
    {
        $crawler = new Crawler($this->renderRadioGroupField($this->createRadioGroupField()));

        $this->assertCount(0, $crawler->filter('input[type="radio"][checked]'), $crawler->html());
    }

    public function testRadioGroupSelectsZeroOptionWhenConfiguredAsDefault(): void
    {
        $field = $this->createRadioGroupField();
        $field->setDefaultValue('0');

        $crawler = new Crawler($this->renderRadioGroupField($field));

        $this->assertCount(1, $crawler->filter('input[type="radio"][value="0"][checked]'), $crawler->html());
    }

    private function createField(?string $fieldWidth = null): Field
    {
        $field = new Field();
        $field->setType('text');
        $field->setLabel(self::FIELD_LABEL);
        $field->setAlias('test_field');
        $field->setOrder(1);

        if (null !== $fieldWidth) {
            $field->setFieldWidth($fieldWidth);
        }

        return $field;
    }

    private function renderTextField(Field $field): string
    {
        $twig     = $this->getContainer()->get(Environment::class);
        $template = $twig->load(self::TEXT_FIELD_TEMPLATE);

        return $template->render([
            'field'          => $field,
            'fields'         => [],
            'id'             => 'test_id',
            'formName'       => 'test_form',
            'containerClass' => 'text',
            'type'           => 'text',
            'inputClass'     => 'input',
        ]);
    }

    private function createRadioGroupField(): Field
    {
        $field = new Field();
        $field->setType('radiogrp');
        $field->setLabel(self::FIELD_LABEL);
        $field->setAlias('test_radio_group');
        $field->setOrder(1);
        $field->setProperties([
            'syncList'   => 0,
            'optionlist' => [
                'list' => [
                    ['label' => 'Zero', 'value' => 0],
                    ['label' => 'One', 'value' => 1],
                ],
            ],
        ]);

        return $field;
    }

    private function renderRadioGroupField(Field $field): string
    {
        $twig     = $this->getContainer()->get(Environment::class);
        $template = $twig->load(self::RADIO_GROUP_FIELD_TEMPLATE);

        return $template->render([
            'field'    => $field,
            'fields'   => [],
            'id'       => 'test_id',
            'formName' => 'test_form',
        ]);
    }
}
