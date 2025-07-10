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
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [
                    FormEvents::PRE_SET_DATA,
                    $this->callback(
                        function (callable $formModifier) {
                            /** @var FormInterface<FormBuilderInterface>|MockObject $form */
                            $form = $this->createMock(FormInterface::class);
                            $data = [
                                'field'    => 'address1',
                                'object'   => 'lead',
                                'operator' => 'eq',
                            ];

                            $form->expects($this->exactly(2))
                                ->method('add');

                            $form->expects($this->once())
                                ->method('get')
                                ->willReturnSelf();

                            $this->formAdjustmentsProvider->expects($this->once())
                                ->method('adjustForm');

                            $formModifier(new FormEvent($form, $data));

                            return true;
                        }
                    ),
                ],
                // Adding a filter with a deleted field:
                [
                    FormEvents::PRE_SUBMIT,
                    $this->callback(
                        function (callable $formModifier) {
                            /** @var FormInterface<FormBuilderInterface>|MockObject $form */
                            $form = $this->createMock(FormInterface::class);
                            $data = [
                                'field'    => 'deleted',
                                'object'   => 'lead',
                                'operator' => 'eq',
                            ];

                            $form->expects($this->exactly(2))
                                ->method('add');

                            $form->expects($this->never())
                                ->method('get')
                                ->willReturnSelf();

                            $this->formAdjustmentsProvider->expects($this->never())
                                ->method('adjustForm');

                            $formModifier(new FormEvent($form, $data));

                            return true;
                        }
                    ),
                ]
            );

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

        // Adding a filter with an existing field:
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [
                    FormEvents::PRE_SET_DATA,
                    $this->callback(
                        function (callable $formModifier) {
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
                                public function add($child, string $type = null, array $options = []): static
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

                            return true;
                        }
                    ),
                ],
                [
                    FormEvents::PRE_SUBMIT,
                    function (callable $formModifier): void {
                        // don't do anything for this test
                    },
                ]
            );

        $this->form->buildForm($builder, $options);
    }
}
