<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\EventListener;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectCreateEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectFindEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use MauticPlugin\IntegrationsBundle\EventListener\ContactObjectSubscriber;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Symfony\Component\Routing\Router;

class ContactObjectSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactObjectHelper;

    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
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
}
