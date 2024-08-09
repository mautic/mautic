<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\EventListener\CompanySubscriber;
use Mautic\LeadBundle\LeadEvents;

class CompanySubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $auditLogModel  = $this->createMock(AuditLogModel::class);
        $subscriber     = new CompanySubscriber($ipLookupHelper, $auditLogModel);

        $this->assertEquals(
            [
                LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
                LeadEvents::COMPANY_POST_DELETE => ['onCompanyDelete', 0],
            ],
            $subscriber->getSubscribedEvents()
        );
    }

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
            ->will($this->returnValue($ip));

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber = new CompanySubscriber($ipLookupHelper, $auditLogModel);

        $company            = $this->createMock(Company::class);
        $company->deletedId = $companyId;
        $company->expects($this->once())
            ->method('getPrimaryIdentifier')
            ->will($this->returnValue($companyName));

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->will($this->returnValue($company));

        $subscriber->onCompanyDelete($event);
    }

    /**
     * Test create or update company logging.
     *
     * @param bool $isNew
     */
    private function onCompanyPostSaveMethodCall($isNew): void
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
            ->will($this->returnValue($ip));

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($log);

        $subscriber = new CompanySubscriber($ipLookupHelper, $auditLogModel);

        $company = $this->createMock(Company::class);
        $company->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($companyId));

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->will($this->returnValue($company));
        $event->expects($this->once())
            ->method('getChanges')
            ->will($this->returnValue($changes));
        $event->expects($this->once())
            ->method('isNew')
            ->will($this->returnValue($isNew));

        $subscriber->onCompanyPostSave($event);
    }
}
