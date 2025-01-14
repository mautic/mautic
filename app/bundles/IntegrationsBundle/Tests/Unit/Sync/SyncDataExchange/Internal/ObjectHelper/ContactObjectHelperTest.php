<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\TestCase;

class ContactObjectHelperTest extends TestCase
{
    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $model;

    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldModel;

    /**
     * @var DoNotContact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doNotContactModel;

    protected function setUp(): void
    {
        $this->model             = $this->createMock(LeadModel::class);
        $this->repository        = $this->createMock(LeadRepository::class);
        $this->connection        = $this->createMock(Connection::class);
        $this->fieldModel        = $this->createMock(FieldModel::class);
        $this->doNotContactModel = $this->createMock(DoNotContact::class);

        $this->fieldModel->method('getFieldList')
            ->willReturn(
                [
                    'email'                                => [],
                    MauticSyncDataExchange::OBJECT_COMPANY => [],
                ]
            );
    }

    public function testCreate(): void
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objects = [
            new ObjectChangeDAO('Test', Contact::NAME, null, 'MappedObject', 1, new \DateTime()),
            new ObjectChangeDAO('Test', Contact::NAME, null, 'MappedObject', 2, new \DateTime()),
        ];

        $objectMappings = $this->getObjectHelper()->create($objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals(Contact::NAME, $objectMapping->getInternalObjectName());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertEquals($objects[$key]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testUpdate(): void
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objectChangeDaoA = new ObjectChangeDAO('Test', Contact::NAME, 0, 'MappedObject', 0, new \DateTime());
        $objectChangeDaoB = new ObjectChangeDAO('Test', Contact::NAME, 1, 'MappedObject', 1, new \DateTime());
        $objects          = [$objectChangeDaoA, $objectChangeDaoB];
        $companyId        = 1234;
        $companyValue     = new ReferenceValueDAO();
        $companyValue->setValue($companyId);
        $companyValue->setType(MauticSyncDataExchange::OBJECT_COMPANY);

        $emailField       = new FieldDAO('email', new NormalizedValueDAO('email', 'john@doe.com'));
        $companyField     = new FieldDAO(
            MauticSyncDataExchange::OBJECT_COMPANY,
            new NormalizedValueDAO('reference', $companyValue, $companyValue)
        );

        $objectChangeDaoA->addField($emailField);
        $objectChangeDaoA->addField($companyField);

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
                    $contact2,
                ]
            );

        $queryBuilder = new QueryBuilder($this->connection);
        $statement    = $this->createMock(Statement::class);

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                'SELECT c.companyname FROM '.MAUTIC_TABLE_PREFIX.'companies c WHERE c.id = :id',
                ['id' => $companyId]
            )
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('Company A');

        $contact1->expects($this->exactly(2))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['email', 'john@doe.com'],
                [MauticSyncDataExchange::OBJECT_COMPANY, 'Company A']
            );

        $objectMappings = $this->getObjectHelper()->update([3, 4], $objects);

        foreach ($objectMappings as $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertTrue(isset($objects[$objectMapping->getIntegrationObjectId()]));
            $this->assertEquals($objects[$objectMapping->getIntegrationObjectId()]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testDoNotContactIsAdded(): void
    {
        $this->doNotContactModel->expects($this->once())
            ->method('addDncForContact')
            ->with(1, 'email', 1, 'Test', true, true, true);

        $objectChangeDAO = new ObjectChangeDAO('Test', Contact::NAME, 1, 'MappedObject', 1, new \DateTime());
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

    public function testDoNotContactIsRemoved(): void
    {
        $this->doNotContactModel->expects($this->once())
            ->method('removeDncForContact')
            ->with(1, 'email');

        $objectChangeDAO = new ObjectChangeDAO('Test', Contact::NAME, 1, 'MappedObject', 1, new \DateTime());
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

    public function testUnrecognizedDoNotContactDefaultsToManualDNC(): void
    {
        $this->doNotContactModel->expects($this->once())
            ->method('addDncForContact')
            ->with(1, 'email', 3, 'Test', true, true, true);

        $objectChangeDAO = new ObjectChangeDAO('Test', Contact::NAME, 1, 'MappedObject', 1, new \DateTime());
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
     * @return ContactObjectHelper
     */
    private function getObjectHelper()
    {
        return new ContactObjectHelper($this->model, $this->repository, $this->connection, $this->fieldModel, $this->doNotContactModel);
    }
}
