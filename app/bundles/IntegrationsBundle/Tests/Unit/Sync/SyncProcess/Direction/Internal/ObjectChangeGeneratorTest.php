<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncProcess\Direction\Internal;

use Mautic\IntegrationsBundle\Exception\RequiredValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectSyncSkippedException;
use Mautic\IntegrationsBundle\Sync\Notification\BulkNotification;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectChangeGeneratorTest extends TestCase
{
    /**
     * @var SyncJudgeInterface&MockObject
     */
    private MockObject $syncJudge;

    /**
     * @var ValueHelper&MockObject
     */
    private MockObject $valueHelper;

    /**
     * @var FieldHelper&MockObject
     */
    private MockObject $fieldHelper;

    /**
     * @var MockObject&BulkNotification
     */
    private MockObject $bulkNotification;

    protected function setUp(): void
    {
        $this->syncJudge        = $this->createMock(SyncJudgeInterface::class);
        $this->valueHelper      = $this->createMock(ValueHelper::class);
        $this->fieldHelper      = $this->createMock(FieldHelper::class);
        $this->bulkNotification = $this->createMock(BulkNotification::class);
    }

    public function testFieldsAreAddedToObjectChangeAndIntegrationFirstNameWins(): void
    {
        $this->valueHelper->method('getValueForMautic')
            ->willReturnCallback(
                fn (NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection) => $normalizedValueDAO
            );

        $integration = 'Test';
        $objectName  = 'Contact';

        $mappingManual = $this->getMappingManual($integration, $objectName);
        $syncReport    = $this->getIntegrationSyncReport($integration, $objectName);

        $internalReportObject = new ReportObjectDAO(Contact::NAME, 1);
        $internalReportObject->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $internalReportObject->addField(new ReportFieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));

        $this->syncJudge->expects($this->exactly(2))
            ->method('adjudicate')
            ->willReturnCallback(
                fn ($mode, InformationChangeRequestDAO $internalInformationChangeRequest, InformationChangeRequestDAO $integrationInformationChangeRequest) => $integrationInformationChangeRequest
            );

        $objectChangeDAO = $this->createObjectGenerator()->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $internalReportObject,
            $syncReport->getObject($objectName, 2)
        );

        $this->assertEquals($integration, $objectChangeDAO->getIntegration());

        // object and object ID should be Mautic's (from the Mautic's POV)
        $this->assertEquals(Contact::NAME, $objectChangeDAO->getObject());
        $this->assertEquals(1, $objectChangeDAO->getObjectId());

        // mapped object and ID should be the integrations
        $this->assertEquals($objectName, $objectChangeDAO->getMappedObject());
        $this->assertEquals(2, $objectChangeDAO->getMappedObjectId());

        // Email should be a required field
        $requiredFields = $objectChangeDAO->getRequiredFields();
        $this->assertTrue(isset($requiredFields['email']));

        // Both fields should be included
        $fields = $objectChangeDAO->getFields();
        $this->assertTrue(isset($fields['email']) && isset($fields['firstname']));

        // First name is presumed to be changed
        $changedFields = $objectChangeDAO->getChangedFields();
        $this->assertTrue(isset($changedFields['firstname']));

        // First name should have changed to Robert because the sync judge returned the integration's information change request
        $this->assertEquals('Robert', $changedFields['firstname']->getValue()->getNormalizedValue());
    }

    public function testFieldsAreAddedToObjectChangeAndInternalFirstNameWins(): void
    {
        $this->valueHelper->method('getValueForMautic')
            ->willReturnCallback(
                fn (NormalizedValueDAO $normalizedValueDAO, string $fieldState, string $syncDirection) => $normalizedValueDAO
            );

        $integration = 'Test';
        $objectName  = 'Contact';

        $mappingManual = $this->getMappingManual($integration, $objectName);
        $syncReport    = $this->getIntegrationSyncReport($integration, $objectName);

        $internalReportObject = new ReportObjectDAO(Contact::NAME, 1);
        $internalReportObject->addField(
            new ReportFieldDAO(
                'email',
                new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')
            )
        );
        $internalReportObject->addField(
            new ReportFieldDAO(
                'firstname',
                new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')
            )
        );

        $this->syncJudge->expects($this->exactly(2))
            ->method('adjudicate')
            ->willReturnCallback(
                fn ($mode, InformationChangeRequestDAO $internalInformationChangeRequest, InformationChangeRequestDAO $integrationInformationChangeRequest) => $internalInformationChangeRequest
            );

        $objectChangeDAO = $this->createObjectGenerator()->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $internalReportObject,
            $syncReport->getObject($objectName, 2)
        );

        $this->assertEquals($integration, $objectChangeDAO->getIntegration());

        // object and object ID should be Mautic's (from the Mautic's POV)
        $this->assertEquals(Contact::NAME, $objectChangeDAO->getObject());
        $this->assertEquals(1, $objectChangeDAO->getObjectId());

        // mapped object and ID should be the integrations
        $this->assertEquals($objectName, $objectChangeDAO->getMappedObject());
        $this->assertEquals(2, $objectChangeDAO->getMappedObjectId());

        // Email should be a required field
        $requiredFields = $objectChangeDAO->getRequiredFields();
        $this->assertTrue(isset($requiredFields['email']));

        // Both fields should be included
        $fields = $objectChangeDAO->getFields();
        $this->assertTrue(isset($fields['email']) && isset($fields['firstname']));

        // First name is presumed to be changed
        $changedFields = $objectChangeDAO->getChangedFields();
        $this->assertTrue(isset($changedFields['firstname']));

        // First name should have changed to Robert because the sync judge returned the integration's information change request
        $this->assertEquals('Bob', $changedFields['firstname']->getValue()->getNormalizedValue());
    }

    public function testRequiredValueRejected(): void
    {
        $exceptionMessage = 'exceptionMessage';

        $this->valueHelper->method('getValueForMautic')
            ->willThrowException(new RequiredValueException($exceptionMessage));

        $integrationName  = 'Test';
        $objectName       = 'Contact';

        $mappingManual = $this->getMappingManual($integrationName, $objectName);
        $syncReport    = $this->getIntegrationSyncReport($integrationName, $objectName);

        $internalReportObject = new ReportObjectDAO(Contact::NAME, 1);
        $internalReportObject->addField(
            new ReportFieldDAO(
                'email',
                new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, '')
            )
        );
        $internalReportObject->addField(
            new ReportFieldDAO(
                'firstname',
                new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')
            )
        );

        $this->syncJudge->expects($this->exactly(2))
            ->method('adjudicate')
            ->willReturnCallback(
                function ($mode, InformationChangeRequestDAO $internalInformationChangeRequest, InformationChangeRequestDAO $integrationInformationChangeRequest) {
                    return $internalInformationChangeRequest;
                }
            );

        $this->bulkNotification->expects($this->exactly(2))
            ->method('addNotification')
            ->withConsecutive(
                [
                    'Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator-Test-lead-email',
                    $exceptionMessage,
                    $integrationName,
                    $objectName,
                    Contact::NAME,
                    0,
                    "Field 'email' for object ID '2' mapped to internal 'email' with value 'test@test.com'",
                ],
                [
                    'Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator-Test-lead-first_name',
                    $exceptionMessage,
                    $integrationName,
                    $objectName,
                    Contact::NAME,
                    0,
                    "Field 'first_name' for object ID '2' mapped to internal 'first_name' with value 'Robert'",
                ]
            );

        $this->createObjectGenerator()->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $internalReportObject,
            $syncReport->getObject($objectName, 2)
        );
    }

    public function testRequiredValueRejectedForExistingObject(): void
    {
        $exceptionMessage = 'exceptionMessage';

        $this->valueHelper->method('getValueForMautic')
            ->willThrowException(new RequiredValueException($exceptionMessage));

        $integrationName = 'Test';
        $objectName      = 'Contact';

        $mappingManual = $this->getMappingManual($integrationName, $objectName);
        $syncReport    = $this->getIntegrationSyncReport($integrationName, $objectName);

        $internalReportObject = new ReportObjectDAO(Contact::NAME, 1);
        $internalReportObject->addField(
            new ReportFieldDAO(
                'email',
                new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, '')
            )
        );
        $internalReportObject->addField(
            new ReportFieldDAO(
                'firstname',
                new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')
            )
        );

        $this->syncJudge->expects($this->exactly(2))
            ->method('adjudicate')
            ->willReturnCallback(
                function ($mode, InformationChangeRequestDAO $internalInformationChangeRequest, InformationChangeRequestDAO $integrationInformationChangeRequest) {
                    return $internalInformationChangeRequest;
                }
            );

        $this->bulkNotification->expects($this->exactly(2))
            ->method('addNotification')
            ->withConsecutive(
                [
                    'Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator-Test-lead-email',
                    $exceptionMessage,
                    $integrationName,
                    $objectName,
                    Contact::NAME,
                    0,
                    "Field 'email' for object ID '2' mapped to internal 'email' with value 'test@test.com'",
                ],
                [
                    'Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator-Test-lead-first_name',
                    $exceptionMessage,
                    $integrationName,
                    $objectName,
                    Contact::NAME,
                    0,
                    "Field 'first_name' for object ID '2' mapped to internal 'first_name' with value 'Robert'",
                ]
            );

        $this->createObjectGenerator()->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $internalReportObject,
            $syncReport->getObject($objectName, 2)
        );
    }

    public function testRequiredValueRejectedForNewObject(): void
    {
        $exceptionMessage = 'exceptionMessage';

        $this->valueHelper->method('getValueForMautic')
            ->willThrowException(new RequiredValueException($exceptionMessage));

        $integrationName = 'Test';
        $objectName      = 'Contact';

        $mappingManual = $this->getMappingManual($integrationName, $objectName);
        $syncReport    = $this->getIntegrationSyncReport($integrationName, $objectName);

        $internalReportObject = new ReportObjectDAO(Contact::NAME, null);
        $internalReportObject->addField(
            new ReportFieldDAO(
                'email',
                new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, '')
            )
        );

        $this->syncJudge->expects($this->exactly(1))
            ->method('adjudicate')
            ->willReturnCallback(
                function ($mode, InformationChangeRequestDAO $internalInformationChangeRequest, InformationChangeRequestDAO $integrationInformationChangeRequest) {
                    return $internalInformationChangeRequest;
                }
            );

        $this->bulkNotification->expects($this->exactly(1))
            ->method('addNotification')
            ->with(
                'Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator-Test-lead-email',
                $exceptionMessage.' New object sync skipped.',
                $integrationName,
                $objectName,
                Contact::NAME,
                0,
                "Field 'email' for object ID '2' mapped to internal 'email' with value 'test@test.com'"
            );

        $this->expectException(ObjectSyncSkippedException::class);

        $this->createObjectGenerator()->getSyncObjectChange(
            $syncReport,
            $mappingManual,
            $mappingManual->getObjectMapping(Contact::NAME, $objectName),
            $internalReportObject,
            $syncReport->getObject($objectName, 2)
        );
    }

    public function testFieldsWithDirectionToIntegrationAreSkipped(): void
    {
        $objectChangeGenerator = new ObjectChangeGenerator(
            new class implements SyncJudgeInterface {
                public function adjudicate(
                    $mode,
                    InformationChangeRequestDAO $leftChangeRequest,
                    InformationChangeRequestDAO $rightChangeRequest,
                ) {
                    return $leftChangeRequest;
                }
            },
            new class extends ValueHelper {
            },
            new class extends FieldHelper {
                public function __construct()
                {
                }

                public function getRequiredFields(string $object): array
                {
                    Assert::assertSame(Contact::NAME, $object);

                    return ['email' => []];
                }
            },
            new class extends BulkNotification {
                public function __construct()
                {
                }
            }
        );

        $integrationName   = 'Integration A';
        $reportDAO         = new ReportDAO($integrationName);
        $mappingManualDAO  = new MappingManualDAO($integrationName);
        $objectMappingDAO  = new ObjectMappingDAO(Contact::NAME, 'Lead');
        $internalObject    = new ReportObjectDAO(Contact::NAME, 123);
        $integrationObject = new ReportObjectDAO('Lead', 'integration-id-1');

        $objectMappingDAO->addFieldMapping('email', 'Email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMappingDAO->addFieldMapping('firstname', 'FirstName', ObjectMappingDAO::SYNC_TO_MAUTIC);
        $objectMappingDAO->addFieldMapping('points', 'Score', ObjectMappingDAO::SYNC_TO_INTEGRATION);

        $integrationObject->addField(new ReportFieldDAO('Email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'john@doe.email')));
        $integrationObject->addField(new ReportFieldDAO('FirstName', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'John')));
        $integrationObject->addField(new ReportFieldDAO('Score', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 40)));

        $reportDAO->addObject($integrationObject);

        $objectChange = $objectChangeGenerator->getSyncObjectChange($reportDAO, $mappingManualDAO, $objectMappingDAO, $internalObject, $integrationObject);

        // The points/Score field should not be recorded as a change because it has direction to integration.
        Assert::assertCount(2, $objectChange->getFields());
        Assert::assertSame('john@doe.email', $objectChange->getField('email')->getValue()->getNormalizedValue());
        Assert::assertSame('John', $objectChange->getField('firstname')->getValue()->getNormalizedValue());
        Assert::assertSame('Lead', $objectChange->getMappedObject());
        Assert::assertSame('integration-id-1', $objectChange->getMappedObjectId());
        Assert::assertSame(Contact::NAME, $objectChange->getObject());
        Assert::assertSame(123, $objectChange->getObjectId());
        Assert::assertSame($integrationName, $objectChange->getIntegration());
    }

    private function getMappingManual(string $integration, string $objectName): MappingManualDAO
    {
        $mappingManual = new MappingManualDAO($integration);
        $objectMapping = new ObjectMappingDAO(Contact::NAME, $objectName);
        $objectMapping->addFieldMapping(
            'email',
            'email',
            ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
            true);
        $objectMapping->addFieldMapping(
            'firstname',
            'first_name'
        );
        $mappingManual->addObjectMapping($objectMapping);

        return $mappingManual;
    }

    private function getIntegrationSyncReport(string $integration, string $objectName): ReportDAO
    {
        $syncReport   = new ReportDAO($integration);
        $reportObject = new ReportObjectDAO($objectName, 2);
        $reportObject->addField(
            new ReportFieldDAO(
                'email',
                new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'),
                ReportFieldDAO::FIELD_REQUIRED
            )
        );
        $reportObject->addField(
            new ReportFieldDAO(
                'first_name',
                new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Robert')
            )
        );

        $syncReport->addObject($reportObject);

        return $syncReport;
    }

    private function createObjectGenerator(): ObjectChangeGenerator
    {
        return new ObjectChangeGenerator(
            $this->syncJudge,
            $this->valueHelper,
            $this->fieldHelper,
            $this->bulkNotification
        );
    }
}
