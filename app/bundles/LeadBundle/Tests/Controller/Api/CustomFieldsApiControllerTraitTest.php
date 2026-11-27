<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\LeadBundle\Controller\Api\CustomFieldsApiControllerTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;

final class CustomFieldsApiControllerTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntityFormOptions(): void
    {
        $result = [
            'field_1' => [
                'label' => 'Field 1',
                'type'  => 'text',
            ],
            'field_2' => [
                'label' => 'Field 2',
                'type'  => 'text',
            ],
        ];

        $paginator = $this->createMock(Paginator::class);
        $paginator->method('getIterator')
            ->willReturn($result);

        $modelFake = $this->createMock(FieldModel::class);
        $modelFake->expects($this->once())
            ->method('getEntities')
            ->willReturn($paginator);

        $controller = new class($modelFake) {
            use CustomFieldsApiControllerTrait;

            private string $entityNameOne = 'lead';

            public function __construct(
                private object $model,
            ) {
            }

            /**
             * @return mixed[]
             */
            public function getEntityFormOptionsPublic(): array
            {
                return $this->getEntityFormOptions();
            }

            public function getModel(?string $name): object
            {
                return $this->model;
            }
        };

        $this->assertSame($result, (array) $controller->getEntityFormOptionsPublic()['fields']); // Calling once, should be live
        $this->assertSame($result, (array) $controller->getEntityFormOptionsPublic()['fields']); // Calling twice, should be cached
    }

    /**
     * @param array<string, mixed> $sentParameters
     * @param array<string, mixed> $formChildren
     * @param array<string, mixed> $expectedParameters
     */
    #[DataProvider('parameterProvider')]
    public function testSetCustomFieldValuesFiltersZeros(array $sentParameters, array $formChildren, array $expectedParameters): void
    {
        $model = new class() {
            /**
             * @var array<string, mixed>
             */
            public array $parameters = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function setFieldValues(Lead $lead, array $parameters, bool $overwriteWithBlank): void
            {
                $this->parameters = $parameters;
            }
        };

        $controller = new class($model) {
            use CustomFieldsApiControllerTrait;

            private string $entityNameOne = 'lead';

            public function __construct(
                private object $model,
            ) {
            }

            public function getModel(?string $name): object
            {
                return $this->model;
            }

            /**
             * @param array<string, mixed> $parameters
             */
            public function setCustomFieldValuesPublic(Lead $lead, Form $form, array $parameters): void
            {
                $this->setCustomFieldValues($lead, $form, $parameters, true);
            }
        };

        $controller->setCustomFieldValuesPublic(new Lead(), $this->createFormStub($formChildren), $sentParameters);

        $this->assertSame($expectedParameters, $model->parameters);
    }

    /**
     * @param array<string, mixed> $children
     */
    private function createFormStub(array $children): Form
    {
        $formFields = [];

        foreach ($children as $name => $data) {
            $formField = $this->createStub(FormInterface::class);
            $formField->method('getName')->willReturn($name);
            $formField->method('getData')->willReturn($data);
            $formFields[] = $formField;
        }

        $form = $this->createStub(Form::class);
        $form->method('getIterator')->willReturn(new \ArrayIterator($formFields));

        return $form;
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>}>
     */
    public static function parameterProvider(): \Generator
    {
        yield 'sent positive fraction' => [['number_field' => 0.5], [], ['number_field' => 0.5]];
        yield 'sent positive fraction string' => [['number_field' => '0.5'], [], ['number_field' => '0.5']];
        yield 'sent negative fraction' => [['number_field' => -0.5], [], ['number_field' => -0.5]];
        yield 'sent integer' => [['number_field' => 5], [], ['number_field' => 5]];
        yield 'sent integer string' => [['number_field' => '5'], [], ['number_field' => '5']];
        yield 'sent integer zero' => [['number_field' => 0], [], ['number_field' => 0]];
        yield 'sent float zero' => [['number_field' => 0.0], [], ['number_field' => 0.0]];
        yield 'sent zero string' => [['number_field' => '0'], [], ['number_field' => '0']];
        yield 'sent decimal zero string' => [['number_field' => '0.00'], [], ['number_field' => '0.00']];
        yield 'sent company integer zero' => [['company' => 0], [], []];
        yield 'sent company float zero' => [['company' => 0.0], [], []];
        yield 'sent company zero string' => [['company' => '0'], [], []];
        yield 'sent company decimal zero string' => [['company' => '0.00'], [], []];
        yield 'sent company name' => [['company' => 'Acme'], [], ['company' => 'Acme']];
        yield 'sent numeric company name' => [['company' => '1'], [], ['company' => '1']];
        yield 'injected positive fraction' => [[], ['number_field' => 0.5], ['number_field' => 0.5]];
        yield 'injected positive fraction string' => [[], ['number_field' => '0.5'], ['number_field' => '0.5']];
        yield 'injected negative fraction' => [[], ['number_field' => -0.5], ['number_field' => -0.5]];
        yield 'injected integer' => [[], ['number_field' => 5], ['number_field' => 5]];
        yield 'injected integer string' => [[], ['number_field' => '5'], ['number_field' => '5']];
        yield 'injected integer zero' => [[], ['number_field' => 0], []];
        yield 'injected float zero' => [[], ['number_field' => 0.0], []];
        yield 'injected zero string' => [[], ['number_field' => '0'], []];
        yield 'injected decimal zero string' => [[], ['number_field' => '0.00'], []];
    }
}
