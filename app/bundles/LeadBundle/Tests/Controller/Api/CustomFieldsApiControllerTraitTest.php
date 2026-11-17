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
     * @param array<string, mixed>                                $parameters
     * @param array<string, mixed>                                $formData
     * @param array<int, array{array<string, mixed>, bool, bool}> $expectedCalls
     */
    #[DataProvider('numericValueProvider')]
    public function testSetCustomFieldValuesKeepsOnlyRequestedNumericZero(array $parameters, array $formData, array $expectedCalls): void
    {
        $model = new class() {
            /**
             * @var array<int, array{array<string, mixed>, bool, bool}>
             */
            public array $calls = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function setFieldValues(Lead $lead, array $parameters, bool $overwriteWithBlank, bool $fetchSocialProfiles = true): void
            {
                $this->calls[] = [$parameters, $overwriteWithBlank, $fetchSocialProfiles];
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

        $children = [];
        foreach ($formData as $name => $data) {
            $child = $this->createStub(FormInterface::class);
            $child->method('getName')->willReturn($name);
            $child->method('getData')->willReturn($data);
            $children[$name] = $child;
        }
        $form = $this->createStub(Form::class);
        $form->method('getIterator')->willReturn(new \ArrayIterator($children));

        $controller->setCustomFieldValuesPublic(new Lead(), $form, $parameters);

        $this->assertSame($expectedCalls, $model->calls);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, array<string, mixed>, array<int, array{array<string, mixed>, bool, bool}>}>
     */
    public static function numericValueProvider(): \Generator
    {
        foreach ([
            'positive fraction'        => 0.5,
            'positive fraction string' => '0.5',
            'negative fraction'        => -0.5,
            'integer'                  => 5,
            'integer string'           => '5',
        ] as $name => $value) {
            yield $name => [['number_field' => $value], [], [[['number_field' => $value], false, true]]];
        }

        // A zero the request sent is written with overwriteWithBlank, as LeadModel::setFieldValues treats 0 as empty
        foreach ([
            'integer zero'        => 0,
            'float zero'          => 0.0,
            'zero string'         => '0',
            'decimal zero string' => '0.00',
        ] as $name => $value) {
            yield $name => [['number_field' => $value], [], [[[], false, true], [['number_field' => $value], true, false]]];
        }

        // A zero the form filled in for a field the request did not send is still dropped
        yield 'zero the form filled in' => [
            ['sent_field' => '0'],
            ['sent_field' => 0.0, 'filled_field' => 0.0],
            [[[], false, true], [['sent_field' => 0.0], true, false]],
        ];

        // An empty company removes the contact's primary company in LeadModel::saveEntity, so a 0 company is still dropped
        yield 'zero company' => [['company' => '0'], [], [[[], false, true]]];
    }
}
