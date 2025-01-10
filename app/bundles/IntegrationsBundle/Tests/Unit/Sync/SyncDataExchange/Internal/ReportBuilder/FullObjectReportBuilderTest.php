<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ReportBuilder;

use Mautic\IntegrationsBundle\Event\InternalCompanyEvent;
use Mautic\IntegrationsBundle\Event\InternalContactEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindByIdEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Company as CompanyEntity;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FullObjectReportBuilderTest extends TestCase
{
    private const INTEGRATION_NAME = 'Test';

    private const TEST_EMAIL = 'test@test.com';

    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    /**
     * @var FieldBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $fieldBuilder;

    private FullObjectReportBuilder $reportBuilder;

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
            ->with('email', $this->anything(), $requestObject, $requestDAO->getSyncToIntegration())
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, self::TEST_EMAIL))
            );

        $internalObject = new Contact();

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
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
                            'email'         => self::TEST_EMAIL,
                            'date_modified' => '2018-10-08 00:30:00',
                        ],
                    ]);

                    return true;
                }),
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(Contact::NAME);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals(self::TEST_EMAIL, $objects[1]->getField('email')->getValue()->getNormalizedValue());
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
            ->with('email', $this->anything(), $requestObject, $requestDAO->getSyncToIntegration())
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, self::TEST_EMAIL))
            );

        $internalObject = new Company();

        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
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
                            'email'         => self::TEST_EMAIL,
                            'date_modified' => '2018-10-08 00:30:00',
                        ],
                    ]);

                    return true;
                }),
                IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(MauticSyncDataExchange::OBJECT_COMPANY);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals(self::TEST_EMAIL, $objects[1]->getField('email')->getValue()->getNormalizedValue());
    }

    /**
     * @throws FieldNotFoundException
     * @throws InvalidValueException
     */
    public function testBuildingContactReportWithFindInternalRecordEvent(): void
    {
        $requestDAO    = new RequestDAO(self::INTEGRATION_NAME, 1, new InputOptionsDAO(['integration' => self::INTEGRATION_NAME]));
        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(Contact::NAME, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->once())
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, $requestDAO->getSyncToIntegration())
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, self::TEST_EMAIL))
            );

        $internalObject = new Contact();

        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->exactly(2))
            ->method('hasListeners')
            ->withConsecutive(
                [IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD],
                [IntegrationEvents::INTEGRATION_BEFORE_FULL_CONTACT_REPORT_BUILD]
            )
            ->willReturn(true, true);

        $contactEntity = new class extends Lead {
            public function getId(): int
            {
                return 1;
            }
        };

        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(
                        function (InternalObjectFindEvent $event) use (
                            $internalObject,
                            $fromDateTime,
                            $toDateTime
                        ) {
                            $this->assertSame($internalObject, $event->getObject());
                            $this->assertSame($fromDateTime, $event->getDateRange()->getFromDate());
                            $this->assertSame($toDateTime, $event->getDateRange()->getToDate());
                            $this->assertSame(0, $event->getStart());
                            $this->assertSame(200, $event->getLimit());

                            // Mock a subscriber:
                            $event->setFoundObjects(
                                [
                                    [
                                        'id'            => 1,
                                        'email'         => self::TEST_EMAIL,
                                        'date_modified' => '2018-10-08 00:30:00',
                                    ],
                                ]
                            );

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                ],
                [
                    $this->callback(
                        function (InternalObjectFindByIdEvent $event) use ($internalObject, $contactEntity) {
                            $this->assertSame($internalObject, $event->getObject());
                            $event->setId($contactEntity->getId());
                            $event->setEntity($contactEntity);

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD,
                ],
                [
                    $this->callback(
                        function (InternalContactEvent $event) use ($contactEntity) {
                            $this->assertSame($contactEntity, $event->getContact());

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_BEFORE_FULL_CONTACT_REPORT_BUILD,
                ]
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(Contact::NAME);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals(self::TEST_EMAIL, $objects[1]->getField('email')->getValue()->getNormalizedValue());
    }

    /**
     * @throws FieldNotFoundException
     * @throws InvalidValueException
     */
    public function testBuildingCompanyReportWithFindInternalRecordEvent(): void
    {
        $requestDAO    = new RequestDAO(self::INTEGRATION_NAME, 1, new InputOptionsDAO(['integration' => self::INTEGRATION_NAME]));
        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(MauticSyncDataExchange::OBJECT_COMPANY, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->once())
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, $requestDAO->getSyncToIntegration())
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, self::TEST_EMAIL))
            );

        $internalObject = new Company();

        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->exactly(2))
            ->method('hasListeners')
            ->withConsecutive(
                [IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD],
                [IntegrationEvents::INTEGRATION_BEFORE_FULL_COMPANY_REPORT_BUILD]
            )
            ->willReturn(true, true);

        $companyEntity = new class extends CompanyEntity {
            public function getId(): int
            {
                return 1;
            }
        };

        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(
                        function (InternalObjectFindEvent $event) use (
                            $internalObject,
                            $fromDateTime,
                            $toDateTime
                        ) {
                            $this->assertSame($internalObject, $event->getObject());
                            $this->assertSame($fromDateTime, $event->getDateRange()->getFromDate());
                            $this->assertSame($toDateTime, $event->getDateRange()->getToDate());
                            $this->assertSame(0, $event->getStart());
                            $this->assertSame(200, $event->getLimit());

                            // Mock a subscriber:
                            $event->setFoundObjects(
                                [
                                    [
                                        'id'            => 1,
                                        'email'         => self::TEST_EMAIL,
                                        'date_modified' => '2018-10-08 00:30:00',
                                    ],
                                ]
                            );

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS,
                ],
                [
                    $this->callback(
                        function (InternalObjectFindByIdEvent $event) use ($internalObject, $companyEntity) {
                            $this->assertSame($internalObject, $event->getObject());
                            $event->setId($companyEntity->getId());
                            $event->setEntity($companyEntity);

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD,
                ],
                [
                    $this->callback(
                        function (InternalCompanyEvent $event) use ($companyEntity) {
                            $this->assertSame($companyEntity, $event->getCompany());

                            return true;
                        }
                    ),
                    IntegrationEvents::INTEGRATION_BEFORE_FULL_COMPANY_REPORT_BUILD,
                ]
            );

        $report  = $this->reportBuilder->buildReport($requestDAO);
        $objects = $report->getObjects(MauticSyncDataExchange::OBJECT_COMPANY);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals(self::TEST_EMAIL, $objects[1]->getField('email')->getValue()->getNormalizedValue());
    }
}
