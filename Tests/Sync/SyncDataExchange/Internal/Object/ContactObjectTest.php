<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncDataExchange\Object;


use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ContactObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class ContactObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LeadModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $model;

    /**
     * @var LeadRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var FieldModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldModel;

    /**
     * @var DoNotContact|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doNotContactModel;

    protected function setUp()
    {
        $this->model = $this->createMock(LeadModel::class);
        $this->repository = $this->createMock(LeadRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->fieldModel = $this->createMock(FieldModel::class);
        $this->doNotContactModel = $this->createMock(DoNotContact::class);

        $this->fieldModel->method('getFieldList')
            ->willReturn(
                [
                    'email' => []
                ]
            );
    }

    public function testCreate()
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objects = [
            new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, null, 'MappedObject', 1, new \DateTime()),
            new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, null, 'MappedObject', 2, new \DateTime()),
        ];

        $objectMappings = $this->getObjectHelper()->create($objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals(MauticSyncDataExchange::OBJECT_CONTACT, $objectMapping->getInternalObjectName());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertEquals($objects[$key]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testUpdate()
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objects = [
            0 => new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, 0, 'MappedObject', 0, new \DateTime()),
            1 => new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, 1, 'MappedObject', 1, new \DateTime()),
        ];

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(0);
        $contact2 = $this->createMock(Lead::class);
        $contact2->method('getId')
            ->willReturn(1);
        $this->model->expects($this->once())
            ->method('getEntities')
            ->willReturn(
                [
                    $contact1,
                    $contact2
                ]
            );
        $objectMappings = $this->getObjectHelper()->update([3,4], $objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertTrue(isset($objects[$objectMapping->getIntegrationObjectId()]));
            $this->assertEquals($objects[$objectMapping->getIntegrationObjectId()]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testDoNotContactIsAdded()
    {
        $this->doNotContactModel->expects($this->once())
            ->method('addDncForContact')
            ->with(1, 'email', 1, 'Test', true, true, true);

        $objectChangeDAO = new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, 1, 'MappedObject', 1, new \DateTime());
        $objectChangeDAO->addField(new FieldDAO('mautic_internal_dnc_email', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 1)));

        $objects = [
            1 => $objectChangeDAO,
        ];

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);

        $this->model->expects($this->once())
            ->method('getEntities')
            ->willReturn([$contact1]);
        $this->getObjectHelper()->update([1], $objects);
    }

    public function testDoNotContactIsRemoved()
    {
        $this->doNotContactModel->expects($this->once())
            ->method('removeDncForContact')
            ->with(1, 'email');

        $objectChangeDAO = new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, 1, 'MappedObject', 1, new \DateTime());
        $objectChangeDAO->addField(new FieldDAO('mautic_internal_dnc_email', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 0)));

        $objects = [
            1 => $objectChangeDAO,
        ];

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);

        $this->model->expects($this->once())
            ->method('getEntities')
            ->willReturn([$contact1]);
        $this->getObjectHelper()->update([1], $objects);
    }

    public function testUnrecognizedDoNotContactDefaultsToManualDNC()
    {
        $this->doNotContactModel->expects($this->once())
            ->method('addDncForContact')
            ->with(1, 'email', 3, 'Test', true, true, true);

        $objectChangeDAO = new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_CONTACT, 1, 'MappedObject', 1, new \DateTime());
        $objectChangeDAO->addField(new FieldDAO('mautic_internal_dnc_email', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 4)));

        $objects = [
            1 => $objectChangeDAO,
        ];

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);

        $this->model->expects($this->once())
            ->method('getEntities')
            ->willReturn([$contact1]);
        $this->getObjectHelper()->update([1], $objects);
    }

    /**
     * @return ContactObject
     */
    private function getObjectHelper()
    {
        return new ContactObject($this->model, $this->repository, $this->connection, $this->fieldModel, $this->doNotContactModel);
    }
}