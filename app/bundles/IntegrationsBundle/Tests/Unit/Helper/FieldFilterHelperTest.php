<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Helper\FieldFilterHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FieldFilterHelperTest extends TestCase
{
    public function testFieldsFilteredByPage(): void
    {
        $integrationObject = $this->getIntegrationObject();
        $fieldFilterHelper = new FieldFilterHelper($integrationObject);

        $fieldFilterHelper->filterFieldsByPage('test', 2, 3);
        $this->assertSame(5, $fieldFilterHelper->getTotalFieldCount());
        $filteredFields = $fieldFilterHelper->getFilteredFields();

        $this->assertArrayNotHasKey('field1', $filteredFields);
        $this->assertArrayNotHasKey('field2', $filteredFields);
        $this->assertArrayNotHasKey('field3', $filteredFields);
        $this->assertArrayHasKey('field4', $filteredFields);
        $this->assertArrayHasKey('field5', $filteredFields);
    }

    public function testFieldsFilteredByKeyword(): void
    {
        $integrationObject = $this->getIntegrationObject();
        $fieldFilterHelper = new FieldFilterHelper($integrationObject);

        $fieldFilterHelper->filterFieldsByKeyword('test', 'three', 1);
        $this->assertSame(1, $fieldFilterHelper->getTotalFieldCount());
        $filteredFields = $fieldFilterHelper->getFilteredFields();

        $this->assertArrayNotHasKey('field1', $filteredFields);
        $this->assertArrayNotHasKey('field2', $filteredFields);
        $this->assertArrayHasKey('field3', $filteredFields);
        $this->assertArrayNotHasKey('field4', $filteredFields);
        $this->assertArrayNotHasKey('field5', $filteredFields);
    }

    public function testFieldsFilteredByKeywordAndPage(): void
    {
        $integrationObject = $this->getIntegrationObject();
        $fieldFilterHelper = new FieldFilterHelper($integrationObject);

        $fieldFilterHelper->filterFieldsByKeyword('test', 'field', 2, 3);
        $this->assertSame(5, $fieldFilterHelper->getTotalFieldCount());
        $filteredFields = $fieldFilterHelper->getFilteredFields();

        $this->assertArrayNotHasKey('field1', $filteredFields);
        $this->assertArrayNotHasKey('field2', $filteredFields);
        $this->assertArrayNotHasKey('field3', $filteredFields);
        $this->assertArrayHasKey('field4', $filteredFields);
        $this->assertArrayHasKey('field5', $filteredFields);
    }

    /**
     * @return MockObject&ConfigFormSyncInterface
     */
    private function getIntegrationObject(): MockObject
    {
        $field1 = $this->createMock(MappedFieldInfoInterface::class);
        $field1->method('getLabel')
            ->willReturn('field one');
        $field2 = $this->createMock(MappedFieldInfoInterface::class);
        $field2->method('getLabel')
            ->willReturn('field two');
        $field3 = $this->createMock(MappedFieldInfoInterface::class);
        $field3->method('getLabel')
            ->willReturn('field three');
        $field4 = $this->createMock(MappedFieldInfoInterface::class);
        $field4->method('getLabel')
            ->willReturn('field four');
        $field5 = $this->createMock(MappedFieldInfoInterface::class);
        $field5->method('getLabel')
            ->willReturn('field five');

        $integrationObject = $this->createMock(ConfigFormSyncInterface::class);
        $integrationObject->method('getAllFieldsForMapping')
            ->willReturn(
                [
                    'field1' => $field1,
                    'field2' => $field2,
                    'field3' => $field3,
                    'field4' => $field4,
                    'field5' => $field5,
                ]
            );

        return $integrationObject;
    }
}
