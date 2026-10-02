<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;

final class CompanySubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testCreateCompany(): void
    {
        /** @var UserModel $userModel */
        $userModel = self::getContainer()->get(UserModel::class);
        $users     = $userModel->getRepository()->findAll();
        $user      = reset($users);
        $this->assertInstanceOf(User::class, $user);

        $company = new Company();
        $company->setName('Test company');
        $company->setOwner($user);
        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $companyModel->saveEntity($company);

        $auditLogRepository = $this->em->getRepository(AuditLog::class);
        $auditLogs          = $auditLogRepository->findOneBy(['bundle' => 'lead', 'object' => 'company', 'action' => 'create', 'objectId' => $company->getId()]);
        $this->assertInstanceOf(AuditLog::class, $auditLogs);
        $auditLogDetail = $auditLogs->getDetails();
        $this->assertArrayHasKey('owner', $auditLogDetail);
        $this->assertSame([null, "Admin User ({$user->getId()})"], $auditLogDetail['owner']);
    }

    public function testCompanyGetsDeletedInLeadsTable(): void
    {
        $company = new Company();
        $company->setName('Test Delete Company');
        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $companyModel->saveEntity($company);

        $lead = new Lead();
        $lead->setFirstname('Test name');
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $leadModel->saveEntity($lead);
        $companyModel->addLeadToCompany($company, $lead);
        $leadModel->saveEntity($lead);

        $leadRepository = $this->em->getRepository(Lead::class);
        $lead           = $leadRepository->findOneBy(['firstname' => 'Test name']);
        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertSame('Test Delete Company', $lead->getCompany());

        $companyModel->deleteEntity($company);

        $this->em->refresh($lead);
        $this->assertNull($lead->getCompany());
    }
}
