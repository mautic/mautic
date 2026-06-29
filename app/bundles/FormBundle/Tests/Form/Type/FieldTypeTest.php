<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Collector\ObjectCollectorInterface;
use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\Form\Type\FormFieldRatingType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldTypeTest extends TypeTestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $translator;
    private \PHPUnit\Framework\MockObject\MockObject $objectCollector;
    private \PHPUnit\Framework\MockObject\MockObject $fieldCollector;
    private \PHPUnit\Framework\MockObject\MockObject $mappedFieldCollector;

    protected function setUp(): void
    {
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->objectCollector      = $this->createMock(ObjectCollectorInterface::class);
        $this->fieldCollector       = $this->createMock(FieldCollectorInterface::class);
        $this->mappedFieldCollector = $this->createMock(AlreadyMappedFieldCollectorInterface::class);

        // Set up expected behavior for objectCollector
        $objectCollection = new ObjectCollection();
        $objectCollection->append(new ObjectCrate('contact', 'Contact'));
        $this->objectCollector->method('getObjects')->willReturn($objectCollection);

        // Set up expected behavior for fieldCollector
        $fieldCollection = new FieldCollection([
            new FieldCrate('1', 'email', 'text', []),
        ]);
        $this->fieldCollector->method('getFields')->willReturn($fieldCollection);

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
                FieldType::class => new FieldType(
                    $this->translator,
                    $this->objectCollector,
                    $this->fieldCollector,
                    $this->mappedFieldCollector
                ),
                FormFieldRatingType::class => new FormFieldRatingType($this->translator),
            ], []),
        ];
    }

    public function testFieldWidthDefaultValue(): void
    {
        $formData = [
            'type'          => 'text',
            'addFieldWidth' => true,
            'formId'        => 1,
        ];

        $form = $this->factory->create(FieldType::class, $formData);
        $view = $form->createView();

        $this->assertArrayHasKey('fieldWidth', $view);
        $fieldWidth = $form->get('fieldWidth');
        $this->assertEquals('100%', $fieldWidth->getData());
    }

    public function testFieldWidthChoices(): void
    {
        $formData = [
            'type'          => 'text',
            'addFieldWidth' => true,
            'formId'        => 1,
        ];

        $form = $this->factory->create(FieldType::class, $formData);
        $view = $form->createView();

        $expectedChoices = [
            '100%'   => 'mautic.form.field.form.field_width.one_hundred',
            '75%'    => 'mautic.form.field.form.field_width.seventy_five',
            '66.66%' => 'mautic.form.field.form.field_width.sixty_six',
            '50%'    => 'mautic.form.field.form.field_width.fifty',
            '33.33%' => 'mautic.form.field.form.field_width.thirty_three',
            '25%'    => 'mautic.form.field.form.field_width.twenty_five',
        ];

        $this->assertArrayHasKey('fieldWidth', $view);
        $fieldWidth = $view->children['fieldWidth'];
        $choices    = $fieldWidth->vars['choices'];

        $this->assertCount(count($expectedChoices), $choices);
        foreach ($choices as $choice) {
            $this->assertArrayHasKey($choice->value, $expectedChoices);
            $this->assertEquals($expectedChoices[$choice->value], $choice->label);
        }
    }

    public function testFieldWidthCustomValue(): void
    {
        $formData = [
            'type'          => 'text',
            'addFieldWidth' => true,
            'fieldWidth'    => '75%',
            'formId'        => 1,
        ];

        $form       = $this->factory->create(FieldType::class, $formData);
        $fieldWidth = $form->get('fieldWidth');
        $this->assertEquals('75%', $fieldWidth->getData());
    }

    public function testRatingFieldUsesRatingPropertiesAndNoDefaultValue(): void
    {
        $formData = [
            'type'       => 'rating',
            'formId'     => 1,
            'properties' => [
                'star_count' => 7,
                'symbol'     => '◆',
                'star_color' => '#112233',
                'base_color' => '#ddeeff',
            ],
        ];

        $form = $this->factory->create(FieldType::class, $formData);

        $this->assertFalse($form->has('defaultValue'));
        $this->assertTrue($form->has('properties'));
        $this->assertTrue($form->get('properties')->has('star_count'));
        $this->assertSame(7, $form->get('properties')->get('star_count')->getData());
        $this->assertSame('◆', $form->get('properties')->get('symbol')->getData());
        $this->assertSame('#112233', $form->get('properties')->get('star_color')->getData());
        $this->assertSame('#ddeeff', $form->get('properties')->get('base_color')->getData());
    }
}
