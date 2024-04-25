<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Form\Type\FormFieldNumberType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;

final class FormFieldNumberTypeTest extends TypeTestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var AbstractType<FormFieldNumberType>
     */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->form        = new FormFieldNumberType();
    }

    public function testBuildFormIfParentIsEmpty(): void
    {
        $options = [
            'data' => [
                'precision' => 0,
            ],
        ];

        $this->formBuilder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'placeholder',
                    TextType::class,
                    [
                        'label'      => 'mautic.form.field.form.property_placeholder',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => ['class' => 'form-control'],
                        'required'   => false,
                    ],
                ],
                [
                    'precision',
                    IntegerType::class,
                    [
                        'label'      => 'mautic.form.field.form.number_precision',
                        'label_attr' => ['class' => 'control-label'],
                        'data'       => 0,
                        'required'   => false,
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.form.field.form.number_precision.tooltip',
                        ],
                    ],
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'placeholder' => 'test',
            'precision'   => 1,
        ];
        $form = $this->factory->create(FormFieldNumberType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertNotEmpty($form->getData());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
