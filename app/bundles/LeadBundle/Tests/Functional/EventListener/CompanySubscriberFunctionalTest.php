<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;

class CompanySubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testCreateCompany(): void
    {
        /** @var UserModel $userModel */
        $userModel = static::getContainer()->get('mautic.user.model.user');
        $users     = $userModel->getRepository()->findAll();
        $user      = reset($users);
        $this->assertInstanceOf(User::class, $user);

        $company = new Company();
        $company->setName('Test company');
        $company->setOwner($user);
        $companyModel = static::getContainer()->get('mautic.lead.model.company');
        $companyModel->saveEntity($company);

        $auditLogRepository = $this->em->getRepository(AuditLog::class);
        $auditLogs          = $auditLogRepository->findOneBy(['bundle' => 'lead', 'object' => 'company', 'action' => 'create', 'objectId' => $company->getId()]);
        $this->assertInstanceOf(AuditLog::class, $auditLogs);
        $auditLogDetail = $auditLogs->getDetails();
        $this->assertArrayHasKey('owner', $auditLogDetail);
        $this->assertSame([null, "Admin User ({$user->getId()})"], $auditLogDetail['owner']);
    }
}
