<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\EventListener;

use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectRouteEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\EventListener\CompanyObjectSubscriber;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\DateRange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;

class CompanyObjectSubscriberTest extends TestCase
{
    /**
     * @var CompanyObjectHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyObjectHelper;

    /**
     * @var Router|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    /**
     * @var CompanyObjectHelper
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->companyObjectHelper = $this->createMock(CompanyObjectHelper::class);
        $this->router              = $this->createMock(Router::class);
        $this->subscriber          = new CompanyObjectSubscriber(
            $this->companyObjectHelper,
            $this->router
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
                IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS  => ['updateCompanies', 0],
                IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS  => ['createCompanies', 0],
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS    => [
                    ['findCompaniesByIds', 0],
                    ['findCompaniesByDateRange', 0],
                    ['findCompaniesByFieldValues', 0],
                ],
                IntegrationEvents::INTEGRATION_FIND_OWNER_IDS              => ['findOwnerIdsForCompanies', 0],
                IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE => ['buildCompanyRoute', 0],
            ],
            CompanyObjectSubscriber::getSubscribedEvents()
        );
    }

    public function testCollectInternalObjects(): void
    {
        $event = new InternalObjectEvent();

        $this->subscriber->collectInternalObjects($event);

        $this->assertCount(1, $event->getObjects());
        $this->assertInstanceOf(
            Company::class,
            $event->getObjects()[0]
        );
    }

    public function testUpdateCompaniesWithWrongObject(): void
    {
        $event = new InternalObjectUpdateEvent(new Contact(), [], []);

        $this->companyObjectHelper->expects($this->never())
            ->method('update');

        $this->subscriber->updateCompanies($event);

        $this->assertSame([], $event->getUpdatedObjectMappings());
    }

    public function testUpdateCompaniesWithRightObject(): void
    {
        $event = new InternalObjectUpdateEvent(new Company(), [123], [['id' => 345]]);

        $this->companyObjectHelper->expects($this->once())
            ->method('update')
            ->with([123], [['id' => 345]])
            ->willReturn([['object_mapping_1']]);

        $this->subscriber->updateCompanies($event);

        $this->assertSame([['object_mapping_1']], $event->getUpdatedObjectMappings());
    }

    public function testCreateCompaniesWithWrongObject(): void
    {
        $event = new InternalObjectCreateEvent(new Contact(), []);

        $this->companyObjectHelper->expects($this->never())
            ->method('create');

        $this->subscriber->createCompanies($event);

        $this->assertSame([], $event->getObjectMappings());
    }

    public function testCreateCompaniesWithRightObject(): void
    {
        $event = new InternalObjectCreateEvent(new Company(), [['somefield' => 'somevalue']]);

        $this->companyObjectHelper->expects($this->once())
            ->method('create')
            ->with([['somefield' => 'somevalue']])
            ->willReturn([['object_mapping_1']]);

        $this->subscriber->createCompanies($event);

        $this->assertSame([['object_mapping_1']], $event->getObjectMappings());
    }

    public function testFindCompaniesByIdsWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsByIds');

        $this->subscriber->findCompaniesByIds($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByIdsWithNoIds(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsByIds');

        $this->subscriber->findCompaniesByIds($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByIdsWithRightObject(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $event->setIds([123]);

        $this->companyObjectHelper->expects($this->once())
            ->method('findObjectsByIds')
            ->with([123])
            ->willReturn([['object_1']]);

        $this->subscriber->findCompaniesByIds($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindCompaniesByDateRangeWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsBetweenDates');

        $this->subscriber->findCompaniesByDateRange($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByDateRangeWithNoDateRange(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsBetweenDates');

        $this->subscriber->findCompaniesByDateRange($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByDateRangeWithRightObject(): void
    {
        $event     = new InternalObjectFindEvent(new Company());
        $fromDate  = new \DateTimeImmutable();
        $toDate    = new \DateTimeImmutable();
        $dateRange = new DateRange($fromDate, $toDate);
        $start     = 0;
        $limit     = 10;

        $event->setDateRange($dateRange);
        $event->setStart($start);
        $event->setLimit($limit);

        $this->companyObjectHelper->expects($this->once())
            ->method('findObjectsBetweenDates')
            ->with(
                $fromDate,
                $toDate,
                $start,
                $limit
            )
            ->willReturn([['object_1']]);

        $this->subscriber->findCompaniesByDateRange($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindCompaniesByFieldValuesWithWrongObject(): void
    {
        $event = new InternalObjectFindEvent(new Contact());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $this->subscriber->findCompaniesByFieldValues($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByFieldValuesWithNoIds(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $this->subscriber->findCompaniesByFieldValues($event);

        $this->assertSame([], $event->getFoundObjects());
    }

    public function testFindCompaniesByFieldValuesWithRightObject(): void
    {
        $event = new InternalObjectFindEvent(new Company());

        $event->setFieldValues(['field_a' => 123]);

        $this->companyObjectHelper->expects($this->once())
            ->method('findObjectsByFieldValues')
            ->with(['field_a' => 123])
            ->willReturn([['object_1']]);

        $this->subscriber->findCompaniesByFieldValues($event);

        $this->assertSame([['object_1']], $event->getFoundObjects());
    }

    public function testFindOwnerIdsForCompaniesWithWrongObject(): void
    {
        $event = new InternalObjectOwnerEvent(new Contact(), []);

        $this->companyObjectHelper->expects($this->never())
            ->method('findOwnerIds');

        $this->subscriber->findOwnerIdsForCompanies($event);

        $this->assertSame([], $event->getOwners());
    }

    public function testFindOwnerIdsForCompaniesWithRightObject(): void
    {
        $event = new InternalObjectOwnerEvent(new Company(), [567]);

        $this->companyObjectHelper->expects($this->once())
            ->method('findOwnerIds')
            ->with([567])
            ->willReturn([['object_1']]);

        $this->subscriber->findOwnerIdsForCompanies($event);

        $this->assertSame([['object_1']], $event->getOwners());
    }

    public function testBuildCompanyRouteWithWrongObject(): void
    {
        $event = new InternalObjectRouteEvent(new Contact(), 123);

        $this->router->expects($this->never())
            ->method('generate');

        $this->subscriber->buildCompanyRoute($event);

        $this->assertNull($event->getRoute());
    }

    public function testBuildCompanyRouteWithRightObject(): void
    {
        $event = new InternalObjectRouteEvent(new Company(), 123);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_company_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => 123,
                ]
            )
            ->willReturn('some/route');

        $this->subscriber->buildCompanyRoute($event);

        $this->assertSame('some/route', $event->getRoute());
    }
}
