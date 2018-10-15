<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncDataExchange\Internal\ReportBuilder;


use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class PartialObjectReportBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldChangeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldChangeRepository;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldHelper;

    /**
     * @var ContactObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyObjectHelper;

    /**
     * @var FieldBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldBuilder;

    protected function setUp()
    {
        $this->fieldChangeRepository = $this->createMock(FieldChangeRepository::class);
        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getNormalizedFieldType', 'getFieldObjectName',])
            ->getMock();
        $this->contactObjectHelper = $this->createMock(ContactObjectHelper::class);
        $this->companyObjectHelper = $this->createMock(CompanyObjectHelper::class);
        $this->fieldBuilder        = $this->createMock(FieldBuilder::class);
    }

    public function testTrackedContactChanges()
    {
        $requestDAO = new RequestDAO(1, false, 'Test');

        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(MauticSyncDataExchange::OBJECT_CONTACT, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestObject->addField('firstname');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->at(0))
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, MauticSyncDataExchange::NAME)
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'))
            );

        $fieldChange = [
            'object_type'  => Lead::class,
            'object_id'    => 1,
            'modified_at'  => '2018-10-08 00:30:00',
            'column_name'  => 'firstname',
            'column_type'  => EncodedValueDAO::STRING_TYPE,
            'column_value' => 'Bob'
        ];

        $this->fieldHelper->expects($this->once())
            ->method('getFieldChangeObject')
            ->with($fieldChange)
            ->willReturn(
                new FieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob'))
            );

        // Find and return tracked changes
        $this->fieldChangeRepository->expects($this->once())
            ->method('findChangesBefore')
            ->with(
                'Test',
                Lead::class,
                $toDateTime,
                0
            )
            ->willReturn([$fieldChange]);

        // Find the complete object
        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsByIds')
            ->with([1])
            ->willReturn([
                [
                    'id' => 1,
                    'email' => 'test@test.com',
                    'firstname' => 'Bob'
                ]
            ]);

        $report = $this->getReportBuilder()->buildReport($requestDAO);

        $objects = $report->getObjects(MauticSyncDataExchange::OBJECT_CONTACT);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals('test@test.com', $objects[1]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals('Bob', $objects[1]->getField('firstname')->getValue()->getNormalizedValue());
    }

    public function testTrackedCompanyChanges()
    {
        $requestDAO = new RequestDAO(1, false, 'Test');

        $fromDateTime  = new \DateTimeImmutable('2018-10-08 00:00:00');
        $toDateTime    = new \DateTimeImmutable('2018-10-08 00:01:00');
        $requestObject = new ObjectDAO(MauticSyncDataExchange::OBJECT_COMPANY, $fromDateTime, $toDateTime);
        $requestObject->addField('email');
        $requestObject->addField('companyname');
        $requestDAO->addObject($requestObject);

        $this->fieldBuilder->expects($this->at(0))
            ->method('buildObjectField')
            ->with('email', $this->anything(), $requestObject, MauticSyncDataExchange::NAME)
            ->willReturn(
                new FieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com'))
            );

        $fieldChange = [
            'object_type'  => Company::class,
            'object_id'    => 1,
            'modified_at'  => '2018-10-08 00:30:00',
            'column_name'  => 'firstname',
            'column_type'  => EncodedValueDAO::STRING_TYPE,
            'column_value' => 'Bob'
        ];

        $this->fieldHelper->expects($this->once())
            ->method('getFieldChangeObject')
            ->with($fieldChange)
            ->willReturn(
                new FieldDAO('companyname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob and Cat'))
            );

        // Find and return tracked changes
        $this->fieldChangeRepository->expects($this->once())
            ->method('findChangesBefore')
            ->with(
                'Test',
                Company::class,
                $toDateTime,
                0
            )
            ->willReturn([$fieldChange]);

        // Find the complete object
        $this->companyObjectHelper->expects($this->once())
            ->method('findObjectsByIds')
            ->with([1])
            ->willReturn(
                [
                    [
                        'id'          => 1,
                        'email'       => 'test@test.com',
                        'companyname' => 'Bob and Cat'
                    ]
                ]
            );

        $report = $this->getReportBuilder()->buildReport($requestDAO);

        $objects = $report->getObjects(MauticSyncDataExchange::OBJECT_COMPANY);

        $this->assertTrue(isset($objects[1]));
        $this->assertEquals('test@test.com', $objects[1]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals('Bob and Cat', $objects[1]->getField('companyname')->getValue()->getNormalizedValue());
    }

    /**
     * @return PartialObjectReportBuilder
     */
    private function getReportBuilder()
    {
        return new PartialObjectReportBuilder(
            $this->fieldChangeRepository,
            $this->fieldHelper,
            $this->contactObjectHelper,
            $this->companyObjectHelper,
            $this->fieldBuilder
        );
    }
}