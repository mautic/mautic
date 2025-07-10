<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Form\Type;

use Mautic\ReportBundle\Form\Type\FilterSelectorType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class FilterSelectorTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private MockObject $formBuilder;

    private FilterSelectorType $FilterSelectorType;

    protected function setUp(): void
    {
        $this->formBuilder         = $this->createMock(FormBuilderInterface::class);
        $this->FilterSelectorType  = new FilterSelectorType();
    }

    public function testBuildFormWithTagFilter(): void
    {
        $options = [
            'filterList' => [
                'tag' => 'Tag',
            ],
            'operatorList' => [
                'tag' => [
                    'in'    => 'including',
                    'notIn' => 'excluding',
                ],
            ],
        ];

        $this->formBuilder
            ->method('addEventListener')
            ->withConsecutive(
                [
                    FormEvents::PRE_SET_DATA,
                    $this->callback(
                        function (callable $formModifier) {
                            /** @var FormInterface<FormBuilderInterface>|MockObject $form */
                            $form = $this->createMock(FormInterface::class);
                            $data = [
                                'column'    => 'tag',
                                'glue'      => 'and',
                                'dynamic'   => 0,
                                'condition' => 'in',
                                'value'     => ['1', '2'],
                            ];

                            $form->expects($this->exactly(2))
                                ->method('add')
                                ->withConsecutive([
                                    'condition',
                                    ChoiceType::class,
                                    [
                                        'choices'           => [
                                            'including' => 'in',
                                            'excluding' => 'notIn',
                                        ],
                                        'expanded'          => false,
                                        'multiple'          => false,
                                        'label'             => 'mautic.report.report.label.filtercondition',
                                        'label_attr'        => ['class' => 'control-label filter-condition'],
                                        'placeholder'       => false,
                                        'required'          => false,
                                        'attr'              => [
                                            'class' => 'form-control not-chosen',
                                        ],
                                    ],
                                ], [
                                    'value', CollectionType::class, [
                                        'entry_type'    => TextType::class,
                                        'allow_add'     => true,
                                        'allow_delete'  => true,
                                        'label'         => 'mautic.report.report.label.filtervalue',
                                        'label_attr'    => ['class' => 'control-label'],
                                        'attr'          => ['class' => 'form-control filter-value'],
                                        'required'      => false,
                                    ],
                                ]);

                            $formModifier(new FormEvent($form, $data));

                            return true;
                        }
                    ),
                ],
            );

        $this->FilterSelectorType->buildForm($this->formBuilder, $options);
    }
}
