<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\EventListener;

use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectRouteEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\EventListener\ContactObjectSubscriber;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\DateRange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;

class ContactObjectSubscriberTest extends TestCase
{
    /**
     * @var ContactObjectHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactObjectHelper;

    /**
     * @var Router|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    /**
     * @var ContactObjectSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->contactObjectHelper = $this->createMock(ContactObjectHelper::class);
        $this->router              = $this->createMock(Router::class);
        $this->subscriber          = new ContactObjectSubscriber(
            $this->contactObjectHelper,
            $this->router
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
                IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS  => ['updateContacts', 0],
                IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS  => ['createContacts', 0],
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS    => [
                    ['findContactsByIds', 0],
                    ['findContactsByDateRange', 0],
                    ['findContactsByFieldValues', 0],
                ],
                IntegrationEvents::INTEGRATION_FIND_OWNER_IDS              => ['findOwnerIdsForContacts', 0],
                IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE => ['buildContactRoute', 0],
            ],
            ContactObjectSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectInternalObjects(): void
    {
        $event = new InternalObjectEvent();

        $this->subscriber->collectInternalObjects($event);

        $this->assertCount(1, $event->getObjects());
        $this->assertInstanceOf(
            Contact::class,
            $event->getObjects()[0]
        );
    }

    public function testUpdateContactsWithWrongObject(): void
    {
        $event = new InternalObjectUpdateEvent(new Company(), [], []);

        $this->contactObjectHelper->expects($this->never())
            ->method('update');

        $this->subscriber->updateContacts($event);

        $this->assertSame([], $event->getUpdatedObjectMappings());
    }

    public function testUpdateContactsWithRightObject(): void
    {
        $event = new InternalObjectUpdateEvent(new Contact(), [123], [['id' => 345]]);

        $this->contactObjectHelper->expects($this->once())
            ->method('update')
            ->with([123], [['id' => 345]])
            ->willReturn([['object_mapping_1']]);

        $this->subscriber->updateContacts($event);

        $this->assertSame([['object_mapping_1']], $event->getUpdatedObjectMappings());
    }

    public function testCreateContactsWithWrongObject(): void
    {
        $event = new InternalObjectCreateEvent(new Company(), []);

        $this->contactObjectHelper->expects($this->never())
            ->method('create');

        $this->subscriber->createContacts($event);

        $this->assertSame([], $event->getObjectMappings());
    }

    public function testCreateContactsWithRightObject(): void
    {
        $event = new InternalObjectCreateEvent(new Contact(), [['somefield' => 'somevalue']]);

        $this->contactObjectHelper->expects($this->once())
            ->method('create')
            ->with([['somefield' => 'somevalue']])
            ->willReturn([['object_mapping_1']]);

        $this->subscriber->createContacts($event);

        $this->assertSame([['object_mapping_1']], $event->getObjectMappings());
    }

    public function testFindContactsByIdsWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsByIds');

        $this->subscriber->findContactsByIds($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByIdsWithNoIds(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsByIds');

        $this->subscriber->findContactsByIds($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByIdsWithRightObject(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $event->setIds([123]);

        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsByIds')
            ->with([123])
            ->willReturn([['object_1']]);

        $this->subscriber->findContactsByIds($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindContactsByDateRangeWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsBetweenDates');

        $this->subscriber->findContactsByDateRange($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByDateRangeWithNoDateRange(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsBetweenDates');

        $this->subscriber->findContactsByDateRange($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByDateRangeWithRightObject(): void
    {
        $event     = new InternalObjectFindEvent(new Contact());
        $fromDate  = new \DateTimeImmutable();
        $toDate    = new \DateTimeImmutable();
        $dateRange = new DateRange($fromDate, $toDate);
        $start     = 0;
        $limit     = 10;

        $event->setDateRange($dateRange);
        $event->setStart($start);
        $event->setLimit($limit);

        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsBetweenDates')
            ->with(
                $fromDate,
                $toDate,
                $start,
                $limit
            )
            ->willReturn([['object_1']]);

        $this->subscriber->findContactsByDateRange($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindContactsByFieldValuesWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $this->subscriber->findContactsByFieldValues($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByFieldValuesWithNoIds(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $this->subscriber->findContactsByFieldValues($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindContactsByFieldValuesWithRightObject(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $event->setFieldValues(['field_a' => 123]);

        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsByFieldValues')
            ->with(['field_a' => 123])
            ->willReturn([['object_1']]);

        $this->subscriber->findContactsByFieldValues($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindOwnerIdsForContactsWithWrongObject(): void
    {
        $event = new InternalObjectOwnerEvent(new Company(), []);

        $this->contactObjectHelper->expects($this->never())
            ->method('findOwnerIds');

        $this->subscriber->findOwnerIdsForContacts($event);

        $this->assertSame([], $event->getOwners());
    }

    public function testFindOwnerIdsForContactsWithRightObject(): void
    {
        $event = new InternalObjectOwnerEvent(new Contact(), [567]);

        $this->contactObjectHelper->expects($this->once())
            ->method('findOwnerIds')
            ->with([567])
            ->willReturn([['object_1']]);

        $this->subscriber->findOwnerIdsForContacts($event);

        $this->assertSame([['object_1']], $event->getOwners());
    }

    public function testBuildContactRouteWithWrongObject(): void
    {
        $event = new InternalObjectRouteEvent(new Company(), 123);

        $this->router->expects($this->never())
            ->method('generate');

        $this->subscriber->buildContactRoute($event);

        $this->assertNull($event->getRoute());
    }

    public function testBuildContactRouteWithRightObject(): void
    {
        $event = new InternalObjectRouteEvent(new Contact(), 123);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_contact_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => 123,
                ]
            )
            ->willReturn('some/route');

        $this->subscriber->buildContactRoute($event);

        $this->assertSame('some/route', $event->getRoute());
    }
}
