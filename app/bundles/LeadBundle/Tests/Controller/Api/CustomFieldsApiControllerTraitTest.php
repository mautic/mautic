<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\LeadBundle\Controller\Api\CustomFieldsApiControllerTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Form;

#[AllowMockObjectsWithoutExpectations]
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
                private readonly object $model,
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
     * @param array<string, mixed> $expectedParameters
     */
    #[DataProvider('numericValueProvider')]
    public function testSetCustomFieldValuesFiltersOnlyNumericZero(mixed $value, array $expectedParameters): void
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
                private readonly object $model,
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

        $controller->setCustomFieldValuesPublic(new Lead(), $this->createStub(Form::class), ['number_field' => $value]);

        $this->assertSame($expectedParameters, $model->parameters);
    }

    /**
     * @return \Generator<string, array{mixed, array<string, mixed>}>
     */
    public static function numericValueProvider(): \Generator
    {
        yield 'positive fraction' => [0.5, ['number_field' => 0.5]];
        yield 'positive fraction string' => ['0.5', ['number_field' => '0.5']];
        yield 'negative fraction' => [-0.5, ['number_field' => -0.5]];
        yield 'integer' => [5, ['number_field' => 5]];
        yield 'integer string' => ['5', ['number_field' => '5']];
        yield 'integer zero' => [0, []];
        yield 'float zero' => [0.0, []];
        yield 'zero string' => ['0', []];
        yield 'decimal zero string' => ['0.00', []];
    }
}
