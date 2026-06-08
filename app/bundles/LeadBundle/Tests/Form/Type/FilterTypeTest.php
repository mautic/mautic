<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Form\Type\FilterType;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Provider\FormAdjustmentsProviderInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class FilterTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FormAdjustmentsProviderInterface
     */
    private MockObject $formAdjustmentsProvider;

    /**
     * @var MockObject&ListModel
     */
    private MockObject $listModel;

    private FilterType $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formAdjustmentsProvider = $this->createMock(FormAdjustmentsProviderInterface::class);
        $this->listModel               = $this->createMock(ListModel::class);
        $this->form                    = new FilterType(
            $this->formAdjustmentsProvider,
            $this->listModel
        );
    }

    public function testBuildFormWithTextField(): void
    {
        /** @var MockObject|FormBuilderInterface<FormBuilderInterface> $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        $this->listModel->expects($this->once())
            ->method('getChoiceFields')
            ->willReturn(
                [
                    'lead' => [
                        'address1' => [
                            'label'      => 'Address Line 1',
                            'properties' => [
                                'type' => 'text',
                            ],
                            'object'     => 'lead',
                            'operators'  => [
                                'equals' => 'eq',
                            ],
                        ],
                    ],
                ]
            );
        // Adding a filter with an existing field:
        $matcher = $this->exactly(2);
        $builder->expects($matcher)
            ->method('addEventListener')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
                /** @var FormInterface<FormBuilderInterface>&MockObject $form */
                $form = $this->createMock(FormInterface::class);
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SET_DATA, $parameters[0]);
                    $callback = function (callable $formModifier) use ($form) {
                        $data = [
                            'field'    => 'address1',
                            'object'   => 'lead',
                            'operator' => 'eq',
                        ];

                        $form->expects($this->exactly(2))->method('add');
                        $form->expects($this->once())->method('get');
                        $this->formAdjustmentsProvider->expects($this->once())->method('adjustForm');
                        $formModifier(new FormEvent($form, $data));
                    };
                    $callback($parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SUBMIT, $parameters[0]);
                    $callback = function (callable $formModifier) use ($form) {
                        $data = [
                            'field'    => 'deleted',
                            'object'   => 'lead',
                            'operator' => 'eq',
                        ];

                        $form->expects($this->exactly(2))->method('add');
                        $form->expects($this->never())->method('get');
                        $this->formAdjustmentsProvider->expects($this->never())->method('adjustForm');
                        $formModifier(new FormEvent($form, $data));
                    };
                    $callback($parameters[1]);
                }

                return $builder;
            });

        $this->form->buildForm($builder, $options);
    }

    /**
     * This ensures that legacy segment structure with "0" filter value will show up.
     */
    public function testBuildFormWithNumberField(): void
    {
        /** @var MockObject|FormBuilderInterface<FormBuilderInterface> $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        $this->listModel->expects($this->once())
            ->method('getChoiceFields')
            ->willReturn(
                [
                    'lead' => [
                        'number1' => [
                            'label'      => 'Number 1',
                            'properties' => [
                                'type' => 'number',
                            ],
                            'object'     => 'lead',
                            'operators'  => [
                                'equals' => 'eq',
                            ],
                        ],
                    ],
                ]
            );

        $matcher = $this->exactly(2);

        // Adding a filter with an existing field:
        $builder->expects($matcher)
            ->method('addEventListener')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SET_DATA, $parameters[0]);
                    $callback = function (callable $formModifier) {
                        $form = new class extends Form {
                            public int $addMethodCallCounter = 0;

                            public function __construct()
                            {
                            }

                            /**
                             * @return FormInterface<FormInterface<mixed>>
                             */
                            public function get(string $name): FormInterface
                            {
                                Assert::assertSame('properties', $name);

                                return new class extends Form {
                                    public function __construct()
                                    {
                                    }

                                    public function setData($modelData): static
                                    {
                                        Assert::assertSame(
                                            [
                                                'filter'  => '0',
                                                'display' => null,
                                            ],
                                            $modelData
                                        );

                                        return $this;
                                    }
                                };
                            }

                            /**
                             * @param FormInterface<FormInterface<mixed>>|string $child
                             * @param mixed[]                                    $options
                             */
                            public function add($child, ?string $type = null, array $options = []): static
                            {
                                ++$this->addMethodCallCounter;

                                return $this;
                            }
                        };

                        $this->formAdjustmentsProvider->expects($this->once())
                            ->method('adjustForm');

                        $data = [
                            'field'    => 'number1',
                            'object'   => 'lead',
                            'filter'   => '0',
                            'operator' => 'eq',
                        ];

                        $formModifier(new FormEvent($form, $data));

                        Assert::assertSame(2, $form->addMethodCallCounter);
                    };
                    $callback($parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SUBMIT, $parameters[0]);
                }

                return $builder;
            });

        $this->form->buildForm($builder, $options);
    }
}
