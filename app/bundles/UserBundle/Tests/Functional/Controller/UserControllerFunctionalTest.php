<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\HttpFoundation\Request;

class UserControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testEditUserAction(): void
    {
        $auditLogModel      = $this->createMock(AuditLogModel::class);
        $auditLogRepository = $this->createMock(AuditLogRepository::class);

        $auditLog = $this->auditLogSetter(1, 'Test User', 'user', 'security', 1, 'login', ['username' => 'testuser']);

        $auditLogRepository->method('getLogsForUser')->willReturn([$auditLog]);
        $auditLogModel->method('getRepository')->willReturn($auditLogRepository);

        $userModel = $this->createMock(UserModel::class);

        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@email.com');
        $user->setPassword('password');

        $userModel->method('getEntity')->willReturn($user);

        $crawler        = $this->client->request(Request::METHOD_GET, '/s/users/edit/1');
        $clientResponse = $this->client->getResponse();

        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertStringContainsString('<!-- Recent activity block(audit_log table) -->', $clientResponse->getContent());
    }

    public function auditLogSetter(int $userId, string $userName, string $bundle,
        string $object, int $objectId, string $action, array $details): AuditLog
    {
        $auditLog = new AuditLog();
        $auditLog->setUserId($userId);
        $auditLog->setUserName($userName);
        $auditLog->setBundle($bundle);
        $auditLog->setObject($object);
        $auditLog->setObjectId($objectId);
        $auditLog->setAction($action);
        $auditLog->setDetails($details);

        return $auditLog;
    }
}
