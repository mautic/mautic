<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Form\Type\FormFieldTelType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldTelTypeTest extends TypeTestCase
{
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);

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
                ButtonGroupType::class      => new ButtonGroupType(),
                FormFieldTelType::class     => new FormFieldTelType($this->translator),
                YesNoButtonGroupType::class => new YesNoButtonGroupType(),
            ], []),
        ];
    }

    public function testFormFieldsAreCreated(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => [
                'country'       => 'United States',
                'international' => true,
            ],
        ]);

        self::assertTrue($form->has('country'));
        self::assertTrue($form->has('international'));
        self::assertTrue($form->has('international_validationmsg'));
        self::assertTrue($form->has('country_validationmsg'));
    }

    public function testCountryFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => ['country' => 'United States'],
        ]);

        $countryField = $form->get('country');

        self::assertFalse($countryField->isRequired());
        self::assertSame('mautic.form.field.type.tel.country_validation', $countryField->getConfig()->getOption('label'));
        self::assertSame('mautic.core.none', $countryField->getConfig()->getOption('placeholder'));
        self::assertSame('United States', $countryField->getData());
        self::assertArrayHasKey('United States', $countryField->getConfig()->getOption('choices'));
    }

    public function testCountryValidationMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => [],
        ]);

        $messageField = $form->get('country_validationmsg');
        $attr         = $messageField->getConfig()->getOption('attr');

        self::assertFalse($messageField->isRequired());
        self::assertSame('mautic.form.field.form.validationmsg', $messageField->getConfig()->getOption('label'));
        self::assertSame('form-control', $attr['class']);
        self::assertSame('mautic.form.field.type.tel.country_validationmsg.tooltip', $attr['tooltip']);
        self::assertSame('mautic.form.field.type.tel.country_validationmsg.placeholder', $attr['placeholder']);
    }

    public function testInternationalValidationMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => [],
        ]);

        $messageField = $form->get('international_validationmsg');
        $attr         = $messageField->getConfig()->getOption('attr');

        self::assertFalse($messageField->isRequired());
        self::assertSame('mautic.form.field.form.validationmsg', $messageField->getConfig()->getOption('label'));
        self::assertSame('form-control', $attr['class']);
        self::assertSame('mautic.form.field.type.tel.international_validationmsg.tooltip', $attr['tooltip']);
        self::assertSame('{"formfield_validation_international_1": "checked"}', $attr['data-show-on']);
    }
}
