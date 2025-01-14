<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ReportBuilder;

use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FullObjectReportBuilderTest extends TestCase
{
    private const INTEGRATION_NAME = 'Test';

    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var FieldBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldBuilder;

    /**
     * @var FullObjectReportBuilder
     */
    private $reportBuilder;

    protected function setUp(): void
    {
        $this->objectProvider = $this->createMock(ObjectProvider::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->fieldBuilder   = $this->createMock(FieldBuilder::class);
        $this->reportBuilder  = new FullObjectReportBuilder(
            $this->fieldBuilder,
            $this->objectProvider,
            $this->dispatcher
        );
    }

    public function testBuildingContactReport(): void
    {
        $requestDAO    = new RequestDAO(self::INTEGRATION_NAME, 1, new InputOptionsDAO(['integration' => self::INTEGRATION_NAME]));
        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(Contact::NAME, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->once())
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, MauticSyncDataExchange::NAME)
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'))
            );

        $internalObject = new Contact();

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                $this->callback(function (InternalObjectFindEvent $event) use ($internalObject, $fromDateTime, $toDateTime) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame($fromDateTime, $event->getDateRange()->getFromDate());
                    $this->assertSame($toDateTime, $event->getDateRange()->getToDate());
                    $this->assertSame(0, $event->getStart());
                    $this->assertSame(200, $event->getLimit());

                    // Mock a subscriber:
                    $event->setFoundObjects([
                        [
                            'id'            => 1,
                            'email'         => 'test@test.com',
                            'date_modified' => '2018-10-08 00:30:00',
                        ],
                    ]);

                    return true;
                })
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(Contact::NAME);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals('test@test.com', $objects[1]->getField('email')->getValue()->getNormalizedValue());
    }

    public function testBuildingCompanyReport(): void
    {
        $requestDAO    = new RequestDAO(self::INTEGRATION_NAME, 1, new InputOptionsDAO(['integration' => self::INTEGRATION_NAME]));
        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(MauticSyncDataExchange::OBJECT_COMPANY, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->once())
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, MauticSyncDataExchange::NAME)
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'))
            );

        $internalObject = new Company();

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                $this->callback(function (InternalObjectFindEvent $event) use ($internalObject, $fromDateTime, $toDateTime) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame($fromDateTime, $event->getDateRange()->getFromDate());
                    $this->assertSame($toDateTime, $event->getDateRange()->getToDate());
                    $this->assertSame(0, $event->getStart());
                    $this->assertSame(200, $event->getLimit());

                    // Mock a subscriber:
                    $event->setFoundObjects([
                        [
                            'id'            => 1,
                            'email'         => 'test@test.com',
                            'date_modified' => '2018-10-08 00:30:00',
                        ],
                    ]);

                    return true;
                })
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(MauticSyncDataExchange::OBJECT_COMPANY);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals('test@test.com', $objects[1]->getField('email')->getValue()->getNormalizedValue());
    }
}
