<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\LeadBundle\Controller\Api\CustomFieldsApiControllerTrait;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Assert;

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
        $modelFake->expects(self::once())
            ->method('getEntities')
            ->willReturn($paginator);

        $controller = new class($modelFake) {
            use CustomFieldsApiControllerTrait;

            private object $model;
            private string $entityNameOne = 'lead';

            public function __construct(object $modelFake)
            {
                $this->model = $modelFake;
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

        Assert::assertSame($result, (array) $controller->getEntityFormOptionsPublic()['fields']); // Calling once, should be live
        Assert::assertSame($result, (array) $controller->getEntityFormOptionsPublic()['fields']); // Calling twice, should be cached
    }
}
