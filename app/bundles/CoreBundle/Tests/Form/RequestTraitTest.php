<?php

namespace Mautic\CoreBundle\Tests\Form;

use Mautic\CoreBundle\Form\RequestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormFactoryBuilder;

class RequestTraitTest extends \PHPUnit\Framework\TestCase
{
    use RequestTrait;

    private Form $form;

    protected function setUp(): void
    {
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $formConfigBuilder = new FormConfigBuilder('foo', null, $dispatcher);
        $formConfigBuilder->setFormFactory((new FormFactoryBuilder())->getFormFactory());
        $formConfigBuilder->setCompound(true);
        $formConfigBuilder->setDataMapper(
            $this->createMock(DataMapperInterface::class)
        );
        $fooConfig = $formConfigBuilder->getFormConfig();

        $this->form = new Form($fooConfig);
    }

    public function testMultiSelectPrepareParametersFromRequest(): void
    {
        $params = [
            'multiselect'  => '',
            'multiselect2' => 'input',
            'multiselect3' => ['first'],
            'multiselect4' => 'first|second',
            'multiselect5' => ['first', 'second'],
        ];

        $expectedValues =
            [
                'multiselect'  => [],
                'multiselect2' => ['input'],
                'multiselect3' => ['first'],
                'multiselect4' => ['first', 'second'],
                'multiselect5' => ['first', 'second'],
            ];

        foreach ($params as $alias => $value) {
            $this->form->add(
                $alias,
                ChoiceType::class,
                [
                    'choices'  => [],
                    'multiple' => true,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                ]
            );
        }

        $this->prepareParametersFromRequest($this->form, $params);

        $this->assertSame($expectedValues, $params);
    }

    /**
     * @dataProvider boolProvider
     *
     * @param string|int|bool|null $value
     */
    public function testCleanFieldsBoolean(?bool $expected, $value): void
    {
        $fieldData = ['boolVal' => $value];
        $leadField = [
            'alias' => 'boolVal',
            'type'  => 'boolean',
        ];

        $this->cleanFields($fieldData, $leadField);
        $this->assertSame(['boolVal' => $expected], $fieldData);
    }

    /**
     * @return iterable<array<?int,int|bool|string|null>>
     */
    public static function boolProvider(): iterable
    {
        yield [true, '1'];
        yield [true, 1];
        yield [true, true];
        yield [true, 'Y'];
        yield [true, 'y'];
        yield [true, 'yES'];
        yield [true, 'T'];
        yield [true, 't'];
        yield [true, 'true'];
        yield [null, null];
        yield [false, '0'];
        yield [false, 0];
        yield [false, false];
        yield [false, 'N'];
        yield [false, 'n'];
        yield [false, 'No'];
        yield [false, 'F'];
        yield [false, 'f'];
        yield [false, 'false'];
    }

    public function testCleanFieldsDateTime(): void
    {
        $fieldData = ['fieldDateTime' => '10/10/2022 05:10:25'];
        $leadField = [
            'alias' => 'fieldDateTime',
            'type'  => 'datetime',
        ];
        $expectedValues = ['fieldDateTime' => '2022-10-10 05:10:25'];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsDate(): void
    {
        $fieldData = ['fieldDate' => '10/10/2022'];
        $leadField = [
            'alias' => 'fieldDate',
            'type'  => 'date',
        ];
        $expectedValues = ['fieldDate' => '2022-10-10'];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsTime(): void
    {
        $fieldData = ['fieldTime' => '05:20:10'];
        $leadField = [
            'alias' => 'fieldTime',
            'type'  => 'time',
        ];
        $expectedValues = ['fieldTime' => '05:20:10'];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsWrongDateTime(): void
    {
        $fieldData = ['fieldTime' => 'string'];
        $leadField = [
            'alias' => 'fieldTime',
            'type'  => 'time',
        ];
        $expectedValues = [];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsMultiSelectArray(): void
    {
        $fieldData = ['fieldMultiSelect' => ['o1', 'o2', 'o3']];
        $leadField = [
            'alias' => 'fieldMultiSelect',
            'type'  => 'multiselect',
        ];
        $expectedValues = ['fieldMultiSelect' => ['o1', 'o2', 'o3']];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsMultiSelectString(): void
    {
        $fieldData = ['fieldMultiSelect' => 'o1|o2|o3'];
        $leadField = [
            'alias' => 'fieldMultiSelect',
            'type'  => 'multiselect',
        ];
        $expectedValues = ['fieldMultiSelect' => ['o1', 'o2', 'o3']];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsNumber(): void
    {
        $fieldData = ['fieldFloat' => '3.2'];
        $leadField = [
            'alias' => 'fieldFloat',
            'type'  => 'number',
        ];
        $expectedValues = ['fieldFloat' => 3.2];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testCleanFieldsEmail(): void
    {
        $fieldData = ['fieldEmail' => 'email@domain.com'];
        $leadField = [
            'alias' => 'fieldEmail',
            'type'  => 'email',
        ];
        $expectedValues = ['fieldEmail' => 'email@domain.com'];
        $this->cleanFields($fieldData, $leadField);
        $this->assertSame($expectedValues, $fieldData);
    }

    public function testDateTimePrepareParametersFromRequest(): void
    {
        $params = [
            'datetime'  => '',
            'datetime2' => '2023-01-01 21:00:10',
        ];

        $expectedValues =
            [
                'datetime2' => '2023-01-01 21:00:10',
            ];

        foreach ($params as $alias => $value) {
            $this->form->add(
                $alias,
                DateTimeType::class,
                [
                    'label'    => false,
                    'attr'     => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                ]
            );
        }

        $this->prepareParametersFromRequest($this->form, $params);

        $this->assertSame($expectedValues, $params);
    }
}
