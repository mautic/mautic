<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ReportBuilder;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectFindEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FullObjectReportBuilderTest extends \PHPUnit_Framework_TestCase
{
    private const INTEGRATION_NAME = 'Test';

    /**
     * @var ObjectProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var FieldBuilder|\PHPUnit_Framework_MockObject_MockObject
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
