<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\EventListener\CompanySubscriber;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\TestCase;

final class CompanySubscriberTest extends TestCase
{
    public function testOnCompanyPostSave(): void
    {
        $this->onCompanyPostSaveMethodCall(false); // update company log
        $this->onCompanyPostSaveMethodCall(true); // create company log
    }

    public function testOnCompanyDelete(): void
    {
        $companyId        = 1;
        $companyName      = 'name';
        $ip               = '127.0.0.2';

        $log = [
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $companyId,
            'action'    => 'delete',
            'details'   => ['name', $companyName],
            'ipAddress' => $ip,
        ];

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ip);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $entityManager         = $this->createStub(EntityManager::class);
        $coreParameters        = $this->createStub(CoreParametersHelper::class);
        $companyLeadRepository = $this->createStub(CompanyLeadRepository::class);
        $companyModel          = $this->createStub(CompanyModel::class);
        $subscriber            = new CompanySubscriber(
            $ipLookupHelper,
            $auditLogModel,
            $entityManager,
            $coreParameters,
            $companyLeadRepository,
            $companyModel,
        );

        $company            = new Company();
        $company->deletedId = $companyId;
        $company->setName($companyName);

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $subscriber->onCompanyDelete($event);
    }

    /**
     * Test create or update company logging.
     */
    private function onCompanyPostSaveMethodCall(bool $isNew): void
    {
        $companyId = 1;
        $changes   = ['changes'];
        $ip        = '127.0.0.2';

        $log = [
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $companyId,
            'action'    => ($isNew) ? 'create' : 'update',
            'details'   => $changes,
            'ipAddress' => $ip,
        ];

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn($ip);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);
        $subscriber            = new CompanySubscriber(
            $ipLookupHelper,
            $auditLogModel,
            $this->createStub(EntityManager::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(CompanyLeadRepository::class),
            $this->createStub(CompanyModel::class),
        );

        $company = new Company();
        ReflectionHelper::setEntityId($company, $companyId);

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);
        $event->expects($this->once())
            ->method('getChanges')
            ->willReturn($changes);
        $event->expects($this->once())
            ->method('isNew')
            ->willReturn($isNew);

        $subscriber->onCompanyPostSave($event);
    }
}
