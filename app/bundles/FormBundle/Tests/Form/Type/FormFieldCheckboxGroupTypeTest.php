<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Form\Type\FormFieldCheckboxGroupType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldCheckboxGroupTypeTest extends TypeTestCase
{
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(function (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
                return match ($id) {
                    'mautic.form.field.checkboxgrp.min_message.placeholder' => 'Enter minimum selection message',
                    'mautic.form.field.checkboxgrp.max_message.placeholder' => 'Enter maximum selection message',
                    'mautic.form.field.checkboxgrp.range.invalid'           => 'Maximum must be greater than or equal to minimum',
                    default                                                 => $id,
                };
            });

        parent::setUp();
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                FormFieldCheckboxGroupType::class => new FormFieldCheckboxGroupType($this->translator),
            ], []),
        ];
    }

    public function testFormFieldsAreCreated(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [
                'min_message' => 'Please select at least {count} options',
                'max_message' => 'Please select no more than {count} options',
            ],
        ]);

        $this->assertTrue($form->has('minimum'));
        $this->assertTrue($form->has('min_message'));
        $this->assertTrue($form->has('maximum'));
        $this->assertTrue($form->has('max_message'));
    }

    public function testMinimumFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $minimumField = $form->get('minimum');

        $this->assertFalse($minimumField->isRequired());
        $this->assertEquals('mautic.form.field.checkboxgrp.minimum', $minimumField->getConfig()->getOption('label'));
        $this->assertEquals(['class' => 'control-label'], $minimumField->getConfig()->getOption('label_attr'));
        $this->assertEquals(['class' => 'form-control', 'min' => 0], $minimumField->getConfig()->getOption('attr'));
    }

    public function testMinMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [
                'min_message' => 'Custom min message',
            ],
        ]);

        $minMessageField = $form->get('min_message');

        $this->assertFalse($minMessageField->isRequired());
        $this->assertEquals('mautic.form.field.checkboxgrp.min_message', $minMessageField->getConfig()->getOption('label'));
        $this->assertEquals(['class' => 'control-label'], $minMessageField->getConfig()->getOption('label_attr'));
        $this->assertEquals('Custom min message', $minMessageField->getData());

        $attr = $minMessageField->getConfig()->getOption('attr');
        $this->assertEquals('form-control', $attr['class']);
        $this->assertEquals('Enter minimum selection message', $attr['placeholder']);
        $this->assertEquals('mautic.form.field.checkboxgrp.min_message.tooltip', $attr['tooltip']);
    }

    public function testMaximumFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $maximumField = $form->get('maximum');

        $this->assertFalse($maximumField->isRequired());
        $this->assertEquals('mautic.form.field.checkboxgrp.maximum', $maximumField->getConfig()->getOption('label'));
        $this->assertEquals(['class' => 'control-label'], $maximumField->getConfig()->getOption('label_attr'));
        $this->assertEquals(['class' => 'form-control', 'min' => 0], $maximumField->getConfig()->getOption('attr'));
    }

    public function testMaxMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [
                'max_message' => 'Custom max message',
            ],
        ]);

        $maxMessageField = $form->get('max_message');

        $this->assertFalse($maxMessageField->isRequired());
        $this->assertEquals('mautic.form.field.checkboxgrp.max_message', $maxMessageField->getConfig()->getOption('label'));
        $this->assertEquals(['class' => 'control-label'], $maxMessageField->getConfig()->getOption('label_attr'));
        $this->assertEquals('Custom max message', $maxMessageField->getData());

        $attr = $maxMessageField->getConfig()->getOption('attr');
        $this->assertEquals('form-control', $attr['class']);
        $this->assertEquals('Enter maximum selection message', $attr['placeholder']);
        $this->assertEquals('mautic.form.field.checkboxgrp.max_message.tooltip', $attr['tooltip']);
    }

    public function testValidRangeSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '2',
            'min_message' => 'Please select at least 2 options',
            'maximum'     => '5',
            'max_message' => 'Please select no more than 5 options',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testValidEqualRangeSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '3',
            'min_message' => 'Please select exactly 3 options',
            'maximum'     => '3',
            'max_message' => 'Please select exactly 3 options',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testInvalidRangeSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '5',
            'min_message' => 'Please select at least 5 options',
            'maximum'     => '3',
            'max_message' => 'Please select no more than 3 options',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors(true));

        $errors = $form->getErrors(true);
        $this->assertInstanceOf(FormError::class, $errors[0]);
        $this->assertEquals('Maximum must be greater than or equal to minimum', $errors[0]->getMessage());
    }

    public function testEmptyRangeSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '',
            'min_message' => '',
            'maximum'     => '',
            'max_message' => '',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testOnlyMinimumSet(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '2',
            'min_message' => 'Please select at least 2 options',
            'maximum'     => '',
            'max_message' => '',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testOnlyMaximumSet(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '',
            'min_message' => '',
            'maximum'     => '5',
            'max_message' => 'Please select no more than 5 options',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testZeroValuesSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '0',
            'min_message' => 'No minimum required',
            'maximum'     => '0',
            'max_message' => 'No maximum allowed',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testNegativeValuesSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => '-1',
            'min_message' => 'Invalid minimum',
            'maximum'     => '5',
            'max_message' => 'Valid maximum',
        ]);

        $this->assertFalse($form->isValid());
        $errors = $form->getErrors(true);
        $this->assertGreaterThan(0, count($errors));
    }

    public function testStringValuesSubmission(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [],
        ]);

        $form->submit([
            'minimum'     => 'invalid',
            'min_message' => 'Invalid minimum',
            'maximum'     => 'also_invalid',
            'max_message' => 'Invalid maximum',
        ]);

        $this->assertFalse($form->isValid());
        $errors = $form->getErrors(true);
        $this->assertGreaterThan(0, count($errors));
    }

    public function testFormDataRetrieval(): void
    {
        $form = $this->factory->create(FormFieldCheckboxGroupType::class, [], [
            'data' => [
                'min_message' => 'Default min message',
                'max_message' => 'Default max message',
            ],
        ]);

        $form->submit([
            'minimum'     => '1',
            'min_message' => 'Custom min message',
            'maximum'     => '10',
            'max_message' => 'Custom max message',
        ]);

        $data = $form->getData();

        $this->assertEquals('1', $data['minimum']);
        $this->assertEquals('Custom min message', $data['min_message']);
        $this->assertEquals('10', $data['maximum']);
        $this->assertEquals('Custom max message', $data['max_message']);
    }
}
