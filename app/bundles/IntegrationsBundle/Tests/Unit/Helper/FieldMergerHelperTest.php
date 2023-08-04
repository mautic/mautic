<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Exception\InvalidFormOptionException;
use Mautic\IntegrationsBundle\Helper\FieldMergerHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use PHPUnit\Framework\TestCase;

class FieldMergerHelperTest extends TestCase
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

        $this->assertFalse(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
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

        $this->assertFalse(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
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
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertEquals($updatedFieldMappings['field1']['mappedField'], $mergedFieldMappings['Lead']['field1']['mappedField']);
        $this->assertEquals($updatedFieldMappings['field1']['syncDirection'], $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
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
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field4 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertEquals($updatedFieldMappings['field4']['mappedField'], $mergedFieldMappings['Lead']['field4']['mappedField']);
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
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
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertEquals($fields['Lead']['field4']['mappedField'], $mergedFieldMappings['Lead']['field4']['mappedField']);
        $this->assertEquals($updatedFieldMappings['field4']['syncDirection'], $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
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

        $this->assertFalse(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testDefaultSyncDirectionSetWithExisting(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field4']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testDefaultSyncDirectionSetWithBidirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');

        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertEquals(ObjectMappingDAO::SYNC_BIDIRECTIONALLY, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testDefaultSyncDirectionSetWithIntegrationDirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_INTEGRATION, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testDefaultSyncDirectionSetWithMauticDirectionalSupported(): void
    {
        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testCurrentSyncDirectionOverwrittenWithSupportedDirectionalSync(): void
    {
        $fields = $this->getCurrentFieldMappings();

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

        $this->assertTrue(isset($mergedFieldMappings['Lead']['field1']));
        $this->assertEquals(ObjectMappingDAO::SYNC_TO_MAUTIC, $mergedFieldMappings['Lead']['field1']['syncDirection']);
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field2']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field3']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field4']));
        $this->assertTrue(isset($mergedFieldMappings['Lead']['field5']));
    }

    public function testDefaultSyncDirectionThrowsExceptionIfFieldDoesNotHaveSyncDirectionSupportDefined(): void
    {
        $this->expectException(InvalidFormOptionException::class);

        $fields = $this->getCurrentFieldMappings();
        unset($fields['Lead']['field1']);

        $integrationObject = $this->getIntegrationObject();
        $integrationFields = $integrationObject->getAllFieldsForMapping('Lead');
        /** @var MappedFieldInfoInterface|\PHPUnit\Framework\MockObject\MockObject $field1 */
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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConfigFormSyncInterface
     */
    private function getIntegrationObject(bool $removeFirstField = false): ConfigFormSyncInterface
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
