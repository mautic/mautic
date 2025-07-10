<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\FieldValidatorInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\ReferenceResolverInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderExecutionerTest extends TestCase
{
    private const INTEGRATION_NAME = 'Test';

    /**
     * @var MappingHelper|MockObject
     */
    private MockObject $mappingHelper;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private MockObject $dispatcher;

    /**
     * @var ObjectProvider|MockObject
     */
    private MockObject $objectProvider;

    private OrderExecutioner $orderExecutioner;

    /**
     * @var ReferenceResolverInterface|MockObject
     */
    private MockObject $referenceResolver;

    /**
     * @var FieldValidatorInterface|MockObject
     */
    private MockObject $fieldValidator;

    protected function setup(): void
    {
        $this->mappingHelper     = $this->createMock(MappingHelper::class);
        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $this->objectProvider    = $this->createMock(ObjectProvider::class);
        $this->referenceResolver = $this->createMock(ReferenceResolverInterface::class);
        $this->fieldValidator    = $this->createMock(FieldValidatorInterface::class);
        $this->orderExecutioner  = new OrderExecutioner(
            $this->mappingHelper,
            $this->dispatcher,
            $this->objectProvider,
            $this->referenceResolver,
            $this->fieldValidator
        );
    }

    public function testContactsAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn(new Contact());

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());
                        Assert::assertSame([1, 2], $event->getIdentifiedObjectIds());
                        Assert::assertCount(2, $event->getUpdateObjects());

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());
                        Assert::assertCount(1, $event->getCreateObjects());

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                ]
            );

        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->referenceResolver->expects($this->exactly(2))
            ->method('resolveReferences');

        $this->fieldValidator->expects($this->exactly(2))
            ->method('validateFields');

        $this->orderExecutioner->execute($this->getSyncOrder(Contact::NAME));
    }

    public function testUpdatedObjectsWithoutAnObjectMappingDoesNotGetAddedToObjectMappingsDAO(): void
    {
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn(new Contact());

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());
                        Assert::assertSame([1, 2], $event->getIdentifiedObjectIds());
                        Assert::assertCount(2, $event->getUpdateObjects());

                        $updatedObjectMappings = [];
                        foreach ($event->getUpdateObjects() as $key => $updateObject) {
                            $updatedObjectMappings[] = $updatedObjectMapping = new UpdatedObjectMappingDAO(
                                $updateObject->getIntegration(),
                                $updateObject->getObject(),
                                $updateObject->getObjectId(),
                                new \DateTime()
                            );

                            if (0 !== $key) {
                                // Only inject an object mapping for one of the objects
                                break;
                            }

                            $objectMapping = new ObjectMapping();
                            $objectMapping->setIntegration($updateObject->getIntegration());
                            $objectMapping->setIntegrationObjectName($updateObject->getObject());
                            $objectMapping->setIntegrationObjectId($updateObject->getObjectId());
                            $updatedObjectMapping->setObjectMapping($objectMapping);
                        }

                        $event->setUpdatedObjectMappings($updatedObjectMappings);

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());
                        Assert::assertCount(1, $event->getCreateObjects());

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                ]
            );

        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->referenceResolver->expects($this->exactly(2))
            ->method('resolveReferences');

        $this->fieldValidator->expects($this->exactly(2))
            ->method('validateFields');

        $this->orderExecutioner->execute($this->getSyncOrder(Contact::NAME));
    }

    public function testCompaniesAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn(new Company());

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        Assert::assertSame(Company::NAME, $event->getObject()->getName());
                        Assert::assertSame([1, 2], $event->getIdentifiedObjectIds());
                        Assert::assertCount(2, $event->getUpdateObjects());

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        Assert::assertSame(Company::NAME, $event->getObject()->getName());
                        Assert::assertCount(1, $event->getCreateObjects());

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                ]
            );

        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->referenceResolver->expects($this->exactly(2))
            ->method('resolveReferences');

        $this->fieldValidator->expects($this->exactly(2))
            ->method('validateFields');

        $syncOrder = $this->getSyncOrder(Company::NAME);
        $this->orderExecutioner->execute($syncOrder);
    }

    public function testMixedObjectsAreUpdatedAndCreated(): void
    {
        $this->objectProvider->expects($this->exactly(4))
            ->method('getObjectByName')
            ->withConsecutive(
                [Contact::NAME],
                [Company::NAME],
                [Contact::NAME],
                [Company::NAME]
            )
            ->willReturnOnConsecutiveCalls(
                new Contact(),
                new Company(),
                new Contact(),
                new Company()
            );

        $this->dispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());

                        $updatedObjectMappings = [];
                        foreach ($event->getUpdateObjects() as $updateObject) {
                            $updatedObjectMappings[] = $updatedObjectMapping = new UpdatedObjectMappingDAO(
                                $updateObject->getIntegration(),
                                $updateObject->getObject(),
                                $updateObject->getObjectId(),
                                new \DateTime()
                            );

                            $objectMapping = new ObjectMapping();
                            $objectMapping->setIntegration($updateObject->getIntegration());
                            $objectMapping->setIntegrationObjectName($updateObject->getObject());
                            $objectMapping->setIntegrationObjectId($updateObject->getObjectId());
                            $updatedObjectMapping->setObjectMapping($objectMapping);
                        }
                        $event->setUpdatedObjectMappings($updatedObjectMappings);

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectUpdateEvent $event) {
                        Assert::assertSame(Company::NAME, $event->getObject()->getName());

                        $updatedObjectMappings = [];
                        foreach ($event->getUpdateObjects() as $updateObject) {
                            $updatedObjectMappings[] = $updatedObjectMapping = new UpdatedObjectMappingDAO(
                                $updateObject->getIntegration(),
                                $updateObject->getObject(),
                                $updateObject->getObjectId(),
                                new \DateTime()
                            );

                            $objectMapping = new ObjectMapping();
                            $objectMapping->setIntegration($updateObject->getIntegration());
                            $objectMapping->setIntegrationObjectName($updateObject->getObject());
                            $objectMapping->setIntegrationObjectId($updateObject->getObjectId());
                            $updatedObjectMapping->setObjectMapping($objectMapping);
                        }

                        $event->setUpdatedObjectMappings($updatedObjectMappings);

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        Assert::assertSame(Contact::NAME, $event->getObject()->getName());

                        $createdObjectMappings = [];
                        foreach ($event->getCreateObjects() as $createObject) {
                            $objectMapping = new ObjectMapping();
                            $objectMapping->setIntegration($createObject->getIntegration());
                            $objectMapping->setIntegrationObjectName($createObject->getObject());
                            $objectMapping->setIntegrationObjectId($createObject->getObjectId());

                            $createdObjectMappings[] = $objectMapping;
                        }
                        $event->setObjectMappings($createdObjectMappings);

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                ],
                [
                    $this->callback(function (InternalObjectCreateEvent $event) {
                        Assert::assertSame(Company::NAME, $event->getObject()->getName());

                        $createdObjectMappings = [];
                        foreach ($event->getCreateObjects() as $createObject) {
                            $objectMapping = new ObjectMapping();
                            $objectMapping->setIntegration($createObject->getIntegration());
                            $objectMapping->setIntegrationObjectName($createObject->getObject());
                            $objectMapping->setIntegrationObjectId($createObject->getObjectId());

                            $createdObjectMappings[] = $objectMapping;
                        }
                        $event->setObjectMappings($createdObjectMappings);

                        return true;
                    }),
                    IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS,
                ]
            );

        $this->mappingHelper->expects($this->exactly(2))
            ->method('updateObjectMappings');

        $this->mappingHelper->expects($this->exactly(2))
            ->method('saveObjectMappings');

        // Merge companies and contacts for the test
        $syncOrder        = $this->getSyncOrder(Contact::NAME);
        $companySyncOrder = $this->getSyncOrder(Company::NAME);
        foreach ($companySyncOrder->getChangedObjectsByObjectType(Company::NAME) as $objectChange) {
            $syncOrder->addObjectChange($objectChange);
        }

        $orderMappings = $this->orderExecutioner->execute($syncOrder);
        Assert::assertCount(4, $orderMappings->getUpdatedMappings());
        Assert::assertCount(2, $orderMappings->getNewMappings());
    }

    public function testEmptyObjectsForUpdateDoesNothing(): void
    {
        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, self::INTEGRATION_NAME);
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, 'bar', null, 'bar', 4));

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(InternalObjectCreateEvent::class), IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS);

        $this->orderExecutioner->execute($syncOrder);
    }

    public function testEmptyObjectsForCreateDoesNothing(): void
    {
        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, self::INTEGRATION_NAME);
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, 'bar', 4, 'bar', 4));

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(InternalObjectUpdateEvent::class), IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS);

        $this->orderExecutioner->execute($syncOrder);
    }

    public function testObjectNotFoundExceptionIsLoggedAndNothingElse(): void
    {
        $syncOrder = $this->getSyncOrder('foo');
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, 'bar', 4, 'bar', 4));
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, 'bar', null, 'bar', 4));

        // update and create per object
        $this->objectProvider->expects($this->exactly(4))
            ->method('getObjectByName')
            ->willReturnCallback(
                function (string $objectName) {
                    if ('bar' === $objectName) {
                        throw new ObjectNotFoundException($objectName);
                    }

                    return $this->createMock(ObjectInterface::class);
                }
            );

        // only foo should recognized and processed
        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->orderExecutioner->execute($syncOrder);
    }

    /**
     * @throws \Exception
     */
    private function getSyncOrder(string $objectName): OrderDAO
    {
        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, self::INTEGRATION_NAME);

        // Two updates
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, 1, $objectName, 1));
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, 2, $objectName, 2));

        // One create
        $syncOrder->addObjectChange(new ObjectChangeDAO(self::INTEGRATION_NAME, $objectName, null, $objectName, 3));

        return $syncOrder;
    }
}
