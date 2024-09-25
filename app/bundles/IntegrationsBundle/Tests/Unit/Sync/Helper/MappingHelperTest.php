<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Helper;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MappingHelperTest extends TestCase
{
    /**
     * @var MockObject&FieldsWithUniqueIdentifier
     */
    private MockObject $fieldsWithUniqueIdentifier;

    /**
     * @var ObjectProvider&MockObject
     */
    private MockObject $objectProvider;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private MockObject $dispatcher;

    /**
     * @var ObjectMappingRepository&MockObject
     */
    private MockObject $objectMappingRepository;

    private MappingHelper $mappingHelper;

    protected function setUp(): void
    {
        $this->objectProvider             = $this->createMock(ObjectProvider::class);
        $this->dispatcher                 = $this->createMock(EventDispatcherInterface::class);
        $this->objectMappingRepository    = $this->createMock(ObjectMappingRepository::class);
        $this->fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->mappingHelper              = new MappingHelper(
            $this->fieldsWithUniqueIdentifier,
            $this->objectMappingRepository,
            $this->objectProvider,
            $this->dispatcher
        );
    }

    public function testObjectReturnedIfKnownMappingExists(): void
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

        Assert::assertEquals($internalObjectName, $foundInternalObject->getObject());
        Assert::assertEquals($internalObjectDAO['internal_object_id'], $foundInternalObject->getObjectId());
        Assert::assertEquals($internalObjectDAO['last_sync_date'], $foundInternalObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testMauticObjectSearchedAndEmptyObjectReturnedIfNoIdentifierFieldsAreMapped(): void
    {
        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->willReturn([]);

        $mappingManual        = $this->createMock(MappingManualDAO::class);
        $internalObjectName   = 'Contact';
        $integrationObjectDAO = new ObjectDAO('Object', 1);

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        Assert::assertEquals($internalObjectName, $foundInternalObject->getObject());
        Assert::assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testEmptyObjectIsReturnedWhenMauticContactIsNotFound(): void
    {
        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
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
                $this->callback(
                    function (InternalObjectFindEvent $event) use ($internalObject) {
                        Assert::assertSame($internalObject, $event->getObject());
                        Assert::assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                        return true;
                    }
                ),
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        Assert::assertEquals($internalObjectName, $foundInternalObject->getObject());
        Assert::assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testMauticContactIsFoundAndReturnedAsObjectDAO(): void
    {
        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
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
                $this->callback(
                    function (InternalObjectFindEvent $event) use ($internalObject) {
                        Assert::assertSame($internalObject, $event->getObject());
                        Assert::assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                        // Mock a subscriber.
                        $event->setFoundObjects(
                            [
                                [
                                    'id' => 3,
                                ],
                            ]
                        );

                        return true;
                    }
                ),
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        Assert::assertEquals($internalObjectName, $foundInternalObject->getObject());
        Assert::assertEquals(3, $foundInternalObject->getObjectId());
    }

    public function testMauticCompanyIsFoundAndReturnedAsObjectDAO(): void
    {
        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
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
                $this->callback(
                    function (InternalObjectFindEvent $event) use ($internalObject) {
                        Assert::assertSame($internalObject, $event->getObject());
                        Assert::assertSame(['email' => 'test@test.com'], $event->getFieldValues());

                        // Mock a subscriber.
                        $event->setFoundObjects(
                            [
                                [
                                    'id' => 3,
                                ],
                            ]
                        );

                        return true;
                    }
                ),
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
            );

        $foundInternalObject = $this->mappingHelper->findMauticObject(
            $mappingManual,
            $internalObjectName,
            $integrationObjectDAO
        );

        Assert::assertEquals($internalObjectName, $foundInternalObject->getObject());
        Assert::assertEquals(3, $foundInternalObject->getObjectId());
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

        Assert::assertEquals($objectName, $foundIntegrationObject->getObject());
        Assert::assertEquals($objectId, $foundIntegrationObject->getObjectId());
        Assert::assertEquals($changeDateTime, $foundIntegrationObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testEmptyIntegrationObjectReturnedIfNotMapped(): void
    {
        $objectName = 'Object';
        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn([]);

        $foundIntegrationObject = $this->mappingHelper->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));

        Assert::assertEquals($objectName, $foundIntegrationObject->getObject());
        Assert::assertEquals(null, $foundIntegrationObject->getObjectId());
        Assert::assertEquals(null, $foundIntegrationObject->getChangeDateTime());
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

    public function testObjectMappingIsInjectedIntoUpdatedObjectMappingDAO(): void
    {
        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration('foobar');
        $objectMapping->setIntegrationObjectName('foo');
        $objectMapping->setIntegrationObjectId('1');

        $this->objectMappingRepository->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'integration'           => $objectMapping->getIntegration(),
                    'integrationObjectName' => $objectMapping->getIntegrationObjectName(),
                    'integrationObjectId'   => $objectMapping->getIntegrationObjectId(),
                ]
            )
            ->willReturn($objectMapping);

        $updatedObjectMappingDAO = new UpdatedObjectMappingDAO('foobar', 'foo', 1, new \DateTime());

        $this->mappingHelper->updateObjectMappings([$updatedObjectMappingDAO]);

        Assert::assertSame($objectMapping, $updatedObjectMappingDAO->getObjectMapping());
    }

    public function testObjectMappingIsNotSetIfObjectMappingNotFoundWhenAttemptingToUpdate(): void
    {
        $this->objectMappingRepository->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'integration'           => 'foobar',
                    'integrationObjectName' => 'foo',
                    'integrationObjectId'   => 1,
                ]
            );

        $updatedObjectMappingDAO = new UpdatedObjectMappingDAO('foobar', 'foo', 1, new \DateTime());

        $this->mappingHelper->updateObjectMappings([$updatedObjectMappingDAO]);

        Assert::assertEmpty($updatedObjectMappingDAO->getObjectMapping());
    }
}
