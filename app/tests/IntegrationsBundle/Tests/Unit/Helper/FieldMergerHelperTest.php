<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Exception\InvalidFormOptionException;
use Mautic\IntegrationsBundle\Helper\FieldMergerHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class FieldMergerHelperTest extends TestCase
{
    public function testNonExistingFieldsAreRemoved(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject(true);
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField'   => 'mautic_test_field',
                'syncDirection' => 'bidirectional',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayNotHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testFieldUnsetIfMappingIsDeleted(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayNotHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testCurrentFieldMappingsAreMerged(): void
    {
        $fields            = $this->getCurrentFieldMappings();
        $integrationObject = $this->getIntegrationObject();
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField'   => 'mautic_test_field',
                'syncDirection' => 'mautic',
            ],
        ];

        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(true);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(true);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertEquals($updatedFieldMappings['field1']['mappedField'], $mergedFieldMappings['Lead']['field1']['mappedField']);
        $this->assertEquals($updatedFieldMappings['field1']['syncDirection'], $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testCurrentFieldMappingsAreMergedWithJustMappedFieldUpdated(): void
    {
        $fields            = $this->getCurrentFieldMappings();
        $integrationObject = $this->getIntegrationObject();
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field4' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field4 */
        $field4 = $integrationFields['field4'];
        $field4->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field4->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(false);
        $field4->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertEquals($updatedFieldMappings['field4']['mappedField'], $mergedFieldMappings['Lead']['field4']['mappedField']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testCurrentFieldMappingsAreMergedWithJustSyncDirectionUpdated(): void
    {
        $fields            = $this->getCurrentFieldMappings();
        $integrationObject = $this->getIntegrationObject();
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field4' => [
                'syncDirection' => ObjectMappingDAO::SYNC_TO_INTEGRATION,
            ],
        ];

        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field4 */
        $field4 = $integrationFields['field4'];
        $field4->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field4->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(true);
        $field4->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertEquals($fields['Lead']['field4']['mappedField'], $mergedFieldMappings['Lead']['field4']['mappedField']);
        $this->assertEquals($updatedFieldMappings['field4']['syncDirection'], $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testFieldUnsetIfDirectionIsUpdatedWithoutMappedField(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField'   => '',
                'syncDirection' => 'bidirectional',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayNotHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testDefaultSyncDirectionSetWithExisting(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field4 */
        $field4 = $integrationFields['field4'];
        $field4->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(true);
        $field4->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(true);
        $field4->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field4' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testDefaultSyncDirectionSetWithBidirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');

        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(true);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(true);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertEquals(ObjectMappingDAO::SYNC_BIDIRECTIONALLY, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testDefaultSyncDirectionSetWithIntegrationDirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(true);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_INTEGRATION, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testDefaultSyncDirectionSetWithMauticDirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testCurrentSyncDirectionOverwrittenWithSupportedDirectionalSync(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(true);

        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
        $mergedFieldMappings = $fieldMergerHelper->getFieldMappings();

        $this->assertArrayHasKey('field1', $mergedFieldMappings['Lead']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertArrayHasKey('field2', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field3', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field4', $mergedFieldMappings['Lead']);
        $this->assertArrayHasKey('field5', $mergedFieldMappings['Lead']);
    }

    public function testDefaultSyncDirectionThrowsExceptionIfFieldDoesNotHaveSyncDirectionSupportDefined(): void
    {
        $this->expectException(InvalidFormOptionException::class);

        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface&MockObject $field1 */
        $field1 = $integrationFields['field1'];
        $field1->expects($this->once())
            ->method('isBidirectionalSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToIntegrationSyncEnabled')
            ->willReturn(false);
        $field1->expects($this->once())
            ->method('isToMauticSyncEnabled')
            ->willReturn(false);
        $fieldMergerHelper = new FieldMergerHelper($integrationObject, $fields);

        $updatedFieldMappings = [
            'field1' => [
                'mappedField' => 'mautic_test_field',
            ],
        ];

        $fieldMergerHelper->mergeSyncFieldMapping('Lead', $updatedFieldMappings);
    }

    private function getIntegrationObject(bool $removeFirstField = false): MockObject&ConfigFormSyncInterface
    {
        $field1 = $this->createMock(MappedFieldInfoInterface::class);
        $field1->method('getName')
            ->willReturn('field one');
        $field2 = $this->createMock(MappedFieldInfoInterface::class);
        $field2->method('getName')
            ->willReturn('field two');
        $field3 = $this->createMock(MappedFieldInfoInterface::class);
        $field3->method('getName')
            ->willReturn('field three');
        $field4 = $this->createMock(MappedFieldInfoInterface::class);
        $field4->method('getName')
            ->willReturn('field four');
        $field5 = $this->createMock(MappedFieldInfoInterface::class);
        $field5->method('getName')
            ->willReturn('field five');

        $fields = [
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
            'field4' => $field4,
            'field5' => $field5,
        ];

        if ($removeFirstField) {
            unset($fields['field1']);
        }

        $integrationObject = $this->createMock(ConfigFormSyncInterface::class);
        $integrationObject->method('getAllFieldsForMapping')
            ->willReturn($fields);

        return $integrationObject;
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function getCurrentFieldMappings(): array
    {
        return [
            'Lead' => [
                'field1' => [
                    'mappedField'   => 'mautic_field1',
                    'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ],
                'field2' => [
                    'mappedField'   => 'mautic_field2',
                    'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ],
                'field3' => [
                    'mappedField'   => 'mautic_field3',
                    'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ],
                'field4' => [
                    'mappedField'   => 'mautic_field4',
                    'syncDirection' => ObjectMappingDAO::SYNC_TO_MAUTIC,
                ],
                'field5' => [
                    'mappedField'   => 'mautic_field5',
                    'syncDirection' => ObjectMappingDAO::SYNC_TO_INTEGRATION,
                ],
            ],
        ];
    }
}
