<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
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
        $userModel = self::$container->get('mautic.user.model.user');
        \assert($userModel instanceof UserModel);
        $users     = $userModel->getRepository()->findAll();
        $user      = reset($users);
        \assert($user instanceof User);

        $company = new Company();
        $company->setName('Test company');
        $company->setOwner($user);
        $companyModel = self::$container->get('mautic.lead.model.company');
        \assert($companyModel instanceof CompanyModel);
        $companyModel->saveEntity($company);

        $auditLogRepository = $this->em->getRepository(AuditLog::class);
        $auditLogs          = $auditLogRepository->findOneBy(['bundle' => 'lead', 'object' => 'company', 'action' => 'create', 'objectId' => $company->getId()]);
        \assert($auditLogs instanceof AuditLog);
        $auditLogDetail = $auditLogs->getDetails();
        $this->assertArrayHasKey('owner', $auditLogDetail);
        $this->assertSame([null, $user->getId()], $auditLogDetail['owner']);
    }
}
