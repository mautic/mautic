<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Form\Type\FormFieldTelType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldTelTypeTest extends TypeTestCase
{
    private const COUNTRY_UNITED_STATES = 'United States';

    private MockObject&TranslatorInterface $translator;

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
                'country'       => self::COUNTRY_UNITED_STATES,
                'international' => true,
            ],
        ]);

        $this->assertTrue($form->has('country'));
        $this->assertTrue($form->has('international'));
        $this->assertTrue($form->has('international_validationmsg'));
        $this->assertTrue($form->has('country_validationmsg'));
    }

    public function testCountryFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => ['country' => self::COUNTRY_UNITED_STATES],
        ]);

        $countryField = $form->get('country');

        $this->assertFalse($countryField->isRequired());
        $this->assertSame('mautic.form.field.type.tel.country_validation', $countryField->getConfig()->getOption('label'));
        $this->assertSame('mautic.core.none', $countryField->getConfig()->getOption('placeholder'));
        $this->assertSame(self::COUNTRY_UNITED_STATES, $countryField->getData());
        $this->assertArrayHasKey(self::COUNTRY_UNITED_STATES, $countryField->getConfig()->getOption('choices'));
    }

    public function testCountryValidationMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => [],
        ]);

        $messageField = $form->get('country_validationmsg');
        $attr         = $messageField->getConfig()->getOption('attr');

        $this->assertFalse($messageField->isRequired());
        $this->assertSame('mautic.form.field.form.validationmsg', $messageField->getConfig()->getOption('label'));
        $this->assertSame('form-control', $attr['class']);
        $this->assertSame('mautic.form.field.type.tel.country_validationmsg.tooltip', $attr['tooltip']);
        $this->assertSame('mautic.form.field.type.tel.country_validationmsg.placeholder', $attr['placeholder']);
    }

    public function testInternationalValidationMessageFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldTelType::class, [], [
            'data' => [],
        ]);

        $messageField = $form->get('international_validationmsg');
        $attr         = $messageField->getConfig()->getOption('attr');

        $this->assertFalse($messageField->isRequired());
        $this->assertSame('mautic.form.field.form.validationmsg', $messageField->getConfig()->getOption('label'));
        $this->assertSame('form-control', $attr['class']);
        $this->assertSame('mautic.form.field.type.tel.international_validationmsg.tooltip', $attr['tooltip']);
        $this->assertSame('{"formfield_validation_international_1": "checked"}', $attr['data-show-on']);
    }
}
