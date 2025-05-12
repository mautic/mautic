<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Form\Type\FormFieldConditionType;
use Mautic\FormBundle\Helper\PropertiesAccessor;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormFieldConditionTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FieldModel
     */
    private MockObject $fieldModel;

    /**
     * @var MockObject|PropertiesAccessor
     */
    private MockObject $propertiesAccessor;

    /**
     * @var MockObject&FormBuilderInterface<string|FormBuilderInterface>
     */
    private MockObject $formBuilder;

    private FormFieldConditionType $form;

    protected function setUp(): void
    {
        $this->fieldModel         = $this->createMock(FieldModel::class);
        $this->propertiesAccessor = $this->createMock(PropertiesAccessor::class);
        $this->formBuilder        = $this->createMock(FormBuilderInterface::class);
        $this->form               = new FormFieldConditionType(
            $this->fieldModel,
            $this->propertiesAccessor
        );
    }

    public function testBuildFormIfParentIsEmpty(): void
    {
        $options = [];

        $this->fieldModel->expects($this->never())
            ->method('getSessionFields');

        $this->propertiesAccessor->expects($this->never())
            ->method('getChoices');
        $matcher = $this->exactly(3);

        $this->formBuilder->expects($matcher)
            ->method('add')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('values', $parameters[0]);
                    $this->assertSame(ChoiceType::class, $parameters[1]);
                    $this->assertSame([
                        'choices'  => [],
                        'multiple' => true,
                        'label'    => false,
                        'attr'     => [
                            'class'        => 'form-control',
                            'data-show-on' => '{"formfield_conditions_any_0": "checked","formfield_conditions_expr": "notIn"}',
                        ],
                        'required' => false,
                    ], $parameters[2]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('any', $parameters[0]);
                    $this->assertSame(YesNoButtonGroupType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'mautic.form.field.form.condition.any_value',
                        'attr'  => [
                            'data-show-on' => '{"formfield_conditions_expr": "in"}',
                        ],
                        'data' => false,
                    ], $parameters[2]);
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('expr', $parameters[0]);
                    $this->assertSame(ChoiceType::class, $parameters[1]);
                    $this->assertSame([
                        'choices'  => [
                            'mautic.core.operator.in'    => 'in',
                            'mautic.core.operator.notin' => 'notIn',
                        ],
                        'label'       => false,
                        'placeholder' => false,
                        'attr'        => [
                            'class' => 'form-control',
                        ],
                        'required' => false,
                    ], $parameters[2]);
                }

                return $this->formBuilder;
            });

        $this->form->buildForm($this->formBuilder, $options);
    }

    public function testBuildFormIfParentExists(): void
    {
        $options = [
            'parent' => 'parent_field_id',
            'formId' => 'form_id',
        ];

        $this->fieldModel->expects($this->once())
            ->method('getSessionFields')
            ->with('form_id')
            ->willReturn(['parent_field_id' => ['some_field_props_here']]);

        $this->propertiesAccessor->expects($this->once())
            ->method('getProperties')
            ->with(['some_field_props_here'])
            ->willReturn(['some_choice_here' => 'Some choice here']);

        $this->propertiesAccessor->expects($this->once())
            ->method('getChoices')
            ->with(['some_choice_here' => 'Some choice here'])
            ->willReturn(['some_choice_here' => 'Some choice here']);
        $matcher = $this->any();

        $this->formBuilder->expects($matcher)->method('add')
            ->willReturnCallback(
                function (...$parameters) use ($matcher) {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame('values', $parameters[0]);
                        $this->assertSame(ChoiceType::class, $parameters[1]);
                        $this->assertSame([
                            'choices'  => ['some_choice_here' => 'Some choice here'],
                            'multiple' => true,
                            'label'    => false,
                            'attr'     => [
                                'class'        => 'form-control',
                                'data-show-on' => '{"formfield_conditions_any_0": "checked","formfield_conditions_expr": "notIn"}',
                            ],
                            'required' => false,
                        ], $parameters[2]);
                    }

                    return $this->formBuilder;
                }
            );

        $this->form->buildForm($this->formBuilder, $options);
    }
}
