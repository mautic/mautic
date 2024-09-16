<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Helper;

use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MappingHelperTest extends TestCase
{
    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldModel;

    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var ObjectMappingRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectMappingRepository;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    protected function setUp(): void
    {
        $this->fieldModel              = $this->createMock(FieldModel::class);
        $this->objectProvider          = $this->createMock(ObjectProvider::class);
        $this->dispatcher              = $this->createMock(EventDispatcherInterface::class);
        $this->objectMappingRepository = $this->createMock(ObjectMappingRepository::class);
        $this->mappingHelper           = new MappingHelper(
            $this->fieldModel,
            $this->objectMappingRepository,
            $this->objectProvider,
            $this->dispatcher
        );
    }

    public function testObjectReturnedIfKnwonMappingExists(): void
    {
        $mappingManual        = new MappingManualDAO('test');
        $integrationObjectDAO = new ObjectDAO('Object', 1);

        $internalObjectDAO = [
            'internal_object_id' => 1,
            'last_sync_date'     => '2018-10-01 00:00:00',
            'is_deleted'         => 0,
        ];

        $this->objectMappingRepository->expects($this->once())
            ->method('getInternalObject')
            ->willReturn($internalObjectDAO);

        $internalObjectName  = 'Contact';
        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals($internalObjectDAO['internal_object_id'], $foundInternalObject->getObjectId());
        $this->assertEquals($internalObjectDAO['last_sync_date'], $foundInternalObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testMauticObjectSearchedAndEmptyObjectReturnedIfNoIdentifierFieldsAreMapped(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn([]);

        $mappingManual        = $this->createMock(MappingManualDAO::class);
        $internalObjectName   = 'Contact';
        $integrationObjectDAO = new ObjectDAO('Object', 1);

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testEmptyObjectIsReturnedWhenMauticContactIsNotFound(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email',
                ]
            );

        $internalObject       = new Contact();
        $internalObjectName   = Contact::NAME;
        $integrationObjectDAO = new ObjectDAO('Object', 1);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with($internalObjectName)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                $this->callback(function (InternalObjectFindEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                    return true;
                })
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testMauticContactIsFoundAndReturnedAsObjectDAO(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email',
                ]
            );

        $internalObject       = new Contact();
        $internalObjectName   = Contact::NAME;
        $changeDateTime       = new \DateTime();
        $integrationObjectDAO = new ObjectDAO('Object', 1, $changeDateTime);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');
        $mappingManual->expects($this->exactly(2))
            ->method('getIntegration')
            ->willReturn('Test');

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with($internalObjectName)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                $this->callback(function (InternalObjectFindEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                    // Mock a subscriber.
                    $event->setFoundObjects([
                        [
                            'id' => 3,
                        ],
                    ]);

                    return true;
                })
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(3, $foundInternalObject->getObjectId());
    }

    public function testMauticCompanyIsFoundAndReturnedAsObjectDAO(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email',
                ]
            );

        $internalObject       = new Company();
        $internalObjectName   = Company::NAME;
        $changeDateTime       = new \DateTime();
        $integrationObjectDAO = new ObjectDAO('Object', 1, $changeDateTime);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');
        $mappingManual->expects($this->exactly(2))
            ->method('getIntegration')
            ->willReturn('Test');

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with($internalObjectName)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                $this->callback(function (InternalObjectFindEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                    // Mock a subscriber.
                    $event->setFoundObjects([
                        [
                            'id' => 3,
                        ],
                    ]);

                    return true;
                })
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject(
            $mappingManual,
            $internalObjectName,
            $integrationObjectDAO
        );

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(3, $foundInternalObject->getObjectId());
    }

    public function testIntegrationObjectReturnedIfMapped(): void
    {
        $objectName     = 'Object';
        $objectId       = 1;
        $changeDateTime = '2018-10-08 00:00:00';

        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn(
                [
                    'is_deleted'            => false,
                    'integration_object_id' => $objectId,
                    'last_sync_date'        => $changeDateTime,
                ]
            );

        $foundIntegrationObject = $this->mappingHelper->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));

        $this->assertEquals($objectName, $foundIntegrationObject->getObject());
        $this->assertEquals($objectId, $foundIntegrationObject->getObjectId());
        $this->assertEquals($changeDateTime, $foundIntegrationObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testEmptyIntegrationObjectReturnedIfNotMapped(): void
    {
        $objectName     = 'Object';
        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn([]);

        $foundIntegrationObject = $this->mappingHelper->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));

        $this->assertEquals($objectName, $foundIntegrationObject->getObject());
        $this->assertEquals(null, $foundIntegrationObject->getObjectId());
        $this->assertEquals(null, $foundIntegrationObject->getChangeDateTime());
    }

    public function testDeletedExceptionThrownIfIntegrationObjectHasBeenNotedAsDeleted(): void
    {
        $this->expectException(ObjectDeletedException::class);

        $objectName     = 'Object';
        $objectId       = 1;
        $changeDateTime = '2018-10-08 00:00:00';

        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn(
                [
                    'is_deleted'            => true,
                    'integration_object_id' => $objectId,
                    'last_sync_date'        => $changeDateTime,
                ]
            );

        $this->mappingHelper->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));
    }
}
