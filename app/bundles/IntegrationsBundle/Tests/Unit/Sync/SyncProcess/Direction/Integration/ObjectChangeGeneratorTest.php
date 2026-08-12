<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncProcess\Direction\Integration;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\ObjectChangeGenerator;
use PHPUnit\Framework\TestCase;

final class ObjectChangeGeneratorTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ValueHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $valueHelper;

    protected function setUp(): void
    {
        $this->valueHelper = $this->createMock(ValueHelper::class);
    }

    public function testFieldIsAddedToObjectChange(): void
    {
        $this->valueHelper->expects($this->exactly(2))->method('getValueForIntegration')
            ->willReturnCallback(
                fn (NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection): NormalizedValueDAO => $normalizedValueDAO
            );

        $integration = 'Test';
        $objectName  = 'Contact';

        $mappingManual = $this->getMappingManual($integration, $objectName);
        $syncReport    = $this->getInternalSyncReport();

        $integrationReportObject = new ReportObjectDAO($objectName, 2);
        $integrationReportObject->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $integrationReportObject->addField(new ReportFieldDAO('first_name', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));

        $objectChangeGenerator = $this->getObjectChangeGenerator();
        $objectChangeDAO       = $objectChangeGenerator->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $syncReport->getObject(Contact::NAME, 1),
            $integrationReportObject
        );

        $this->assertSame($integration, $objectChangeDAO->getIntegration());

        // object and object ID should be the integrations (from the integration's POV)
        $this->assertEquals($objectName, $objectChangeDAO->getObject());
        $this->assertEquals(2, $objectChangeDAO->getObjectId());

        // mapped object and ID should be Mautic's
        $this->assertEquals(Contact::NAME, $objectChangeDAO->getMappedObject());
        $this->assertEquals(1, $objectChangeDAO->getMappedObjectId());

        // Email should be a required field
        $requiredFields = $objectChangeDAO->getRequiredFields();
        $this->assertArrayHasKey('email', $requiredFields);

        // Both fields should be included
        $fields = $objectChangeDAO->getFields();
        $this->assertTrue(isset($fields['email']) && isset($fields['first_name']));

        // First name is presumed to be changed
        $changedFields = $objectChangeDAO->getChangedFields();
        $this->assertArrayHasKey('first_name', $changedFields);
    }

    public function testFieldIsNotAddedToObjectChangeIfNotFound(): void
    {
        $this->valueHelper->expects($this->once())->method('getValueForIntegration')
            ->willReturnCallback(
                fn (NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection): NormalizedValueDAO => $normalizedValueDAO
            );

        $integration = 'Test';
        $objectName  = 'Contact';

        $mappingManual = $this->getMappingManual($integration, $objectName);
        $syncReport    = $this->getInternalSyncReport(false);

        $integrationReportObject = new ReportObjectDAO($objectName, 2);
        $integrationReportObject->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $integrationReportObject->addField(new ReportFieldDAO('first_name', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));

        $objectChangeGenerator = $this->getObjectChangeGenerator();
        $objectChangeDAO       = $objectChangeGenerator->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $syncReport->getObject(Contact::NAME, 1),
            $integrationReportObject
        );

        $this->assertSame($integration, $objectChangeDAO->getIntegration());

        // object and object ID should be the integrations (from the integration's POV)
        $this->assertEquals($objectName, $objectChangeDAO->getObject());
        $this->assertEquals(2, $objectChangeDAO->getObjectId());

        // mapped object and ID should be Mautic's
        $this->assertEquals(Contact::NAME, $objectChangeDAO->getMappedObject());
        $this->assertEquals(1, $objectChangeDAO->getMappedObjectId());

        // Email should be a required field
        $requiredFields = $objectChangeDAO->getRequiredFields();
        $this->assertArrayHasKey('email', $requiredFields);

        // First name should not be included because it wasn't found in the internal object
        $fields = $objectChangeDAO->getFields();
        $this->assertArrayNotHasKey('first_name', $fields);
    }

    public function testFieldsWithDirectionToIntegrationAreSkipped(): void
    {
        $objectChangeGenerator = new ObjectChangeGenerator(
            new class() extends ValueHelper {
            }
        );

        $integrationName   = 'Integration A';
        $reportDAO         = new ReportDAO($integrationName);
        $mappingManualDAO  = new MappingManualDAO($integrationName);
        $objectMappingDAO  = new ObjectMappingDAO(Contact::NAME, 'Lead');
        $internalObject    = new ReportObjectDAO(Contact::NAME, 123);
        $integrationObject = new ReportObjectDAO('Lead', 'integration-id-1');

        $objectMappingDAO->addFieldMapping('email', 'Email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMappingDAO->addFieldMapping('firstname', 'FirstName', ObjectMappingDAO::SYNC_TO_INTEGRATION);
        $objectMappingDAO->addFieldMapping('points', 'Score', ObjectMappingDAO::SYNC_TO_MAUTIC);

        $internalObject->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'john@doe.email')));
        $internalObject->addField(new ReportFieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'John')));
        $internalObject->addField(new ReportFieldDAO('points', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 40)));

        $reportDAO->addObject($internalObject);

        $objectChange = $objectChangeGenerator->getSyncObjectChange($reportDAO, $mappingManualDAO, $objectMappingDAO, $internalObject, $integrationObject);

        // The points/Score field should not be recorded as a change because it has direction to Mautic.
        $this->assertCount(2, $objectChange->getFields());
        $this->assertSame('john@doe.email', $objectChange->getField('Email')->getValue()->getNormalizedValue());
        $this->assertSame('John', $objectChange->getField('FirstName')->getValue()->getNormalizedValue());
        $this->assertSame(Contact::NAME, $objectChange->getMappedObject());
        $this->assertSame(123, $objectChange->getMappedObjectId());
        $this->assertSame('integration-id-1', $objectChange->getObjectId());
        $this->assertSame('Lead', $objectChange->getObject());
        $this->assertSame($integrationName, $objectChange->getIntegration());
    }

    private function getMappingManual(string $integration, string $objectName): MappingManualDAO
    {
        $mappingManual = new MappingManualDAO($integration);
        $objectMapping = new ObjectMappingDAO(Contact::NAME, $objectName);
        $objectMapping->addFieldMapping('email', 'email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMapping->addFieldMapping('firstname', 'first_name');
        $mappingManual->addObjectMapping($objectMapping);

        return $mappingManual;
    }

    private function getInternalSyncReport(bool $includeFirstNameField = true): ReportDAO
    {
        $syncReport           = new ReportDAO(MauticSyncDataExchange::NAME);
        $internalReportObject = new ReportObjectDAO(Contact::NAME, 1);
        $internalReportObject->addField(
            new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'), ReportFieldDAO::FIELD_REQUIRED)
        );

        if ($includeFirstNameField) {
            $internalReportObject->addField(new ReportFieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));
        }

        $syncReport->addObject($internalReportObject);

        return $syncReport;
    }

    private function getObjectChangeGenerator(): ObjectChangeGenerator
    {
        return new ObjectChangeGenerator($this->valueHelper);
    }
}
