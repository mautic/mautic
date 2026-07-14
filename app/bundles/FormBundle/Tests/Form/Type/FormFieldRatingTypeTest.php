<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Form\Type\FormFieldRatingType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormFieldRatingTypeTest extends TypeTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnCallback(
            fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => $id
        );

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
                FormFieldRatingType::class => new FormFieldRatingType($this->translator),
            ], []),
        ];
    }

    public function testDefaultFieldConfiguration(): void
    {
        $form = $this->factory->create(FormFieldRatingType::class, [], [
            'data' => [],
        ]);

        $this->assertSame('formfield_rating', $form->getConfig()->getType()->getInnerType()->getBlockPrefix());

        $starCount = $form->get('star_count');
        $this->assertInstanceOf(IntegerType::class, $starCount->getConfig()->getType()->getInnerType());
        $this->assertSame('mautic.form.field.form.rating_star_count', $starCount->getConfig()->getOption('label'));
        $this->assertSame(['class' => 'control-label'], $starCount->getConfig()->getOption('label_attr'));
        $this->assertSame([
            'class'   => 'form-control',
            'tooltip' => 'mautic.form.field.help.rating_star_count',
            'min'     => 1,
            'max'     => 10,
        ], $starCount->getConfig()->getOption('attr'));
        $this->assertSame(5, $starCount->getData());
        $this->assertFalse($starCount->isRequired());

        $symbol = $form->get('symbol');
        $this->assertInstanceOf(ChoiceType::class, $symbol->getConfig()->getType()->getInnerType());
        $this->assertSame('mautic.form.field.form.rating_symbol', $symbol->getConfig()->getOption('label'));
        $this->assertSame(
            ['class' => 'form-control', 'tooltip' => 'mautic.form.field.help.rating_symbol'],
            $symbol->getConfig()->getOption('attr')
        );
        $this->assertSame('★', $symbol->getData());
        $this->assertFalse($symbol->isRequired());

        $choices = $symbol->createView()->vars['choices'];
        $this->assertCount(8, $choices);
        $this->assertSame('★', $choices[0]->value);
        $this->assertSame('mautic.form.field.form.rating_symbol.star_filled_label', $choices[0]->label);
        $this->assertSame('◆', $choices[7]->value);
        $this->assertSame('mautic.form.field.form.rating_symbol.diamond_filled_label', $choices[7]->label);

        $starColor = $form->get('star_color');
        $this->assertInstanceOf(TextType::class, $starColor->getConfig()->getType()->getInnerType());
        $this->assertSame('mautic.form.field.form.rating_star_color', $starColor->getConfig()->getOption('label'));
        $this->assertSame([
            'class'        => 'form-control minicolors-input',
            'tooltip'      => 'mautic.form.field.help.rating_star_color',
            'data-toggle'  => 'color',
            'autocomplete' => 'false',
            'size'         => '7',
        ], $starColor->getConfig()->getOption('attr'));
        $this->assertSame('#f5b301', $starColor->getData());
        $this->assertFalse($starColor->isRequired());

        $baseColor = $form->get('base_color');
        $this->assertInstanceOf(TextType::class, $baseColor->getConfig()->getType()->getInnerType());
        $this->assertSame('mautic.form.field.form.rating_base_color', $baseColor->getConfig()->getOption('label'));
        $this->assertSame([
            'class'        => 'form-control minicolors-input',
            'tooltip'      => 'mautic.form.field.help.rating_base_color',
            'data-toggle'  => 'color',
            'autocomplete' => 'false',
            'size'         => '7',
        ], $baseColor->getConfig()->getOption('attr'));
        $this->assertSame('#cccccc', $baseColor->getData());
        $this->assertFalse($baseColor->isRequired());
    }

    public function testProvidedDataIsUsed(): void
    {
        $form = $this->factory->create(FormFieldRatingType::class, [], [
            'data' => [
                'star_count' => 9,
                'symbol'     => '♡',
                'star_color' => '#123456',
                'base_color' => '#abcdef',
            ],
        ]);

        $this->assertSame(9, $form->get('star_count')->getData());
        $this->assertSame('♡', $form->get('symbol')->getData());
        $this->assertSame('#123456', $form->get('star_color')->getData());
        $this->assertSame('#abcdef', $form->get('base_color')->getData());
    }
}
