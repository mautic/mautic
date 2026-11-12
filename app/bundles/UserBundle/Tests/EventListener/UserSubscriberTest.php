<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\PostSaveUserEvent;
use Mautic\UserBundle\EventListener\UserSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UserSubscriberTest extends TestCase
{
    public function testUserSaveThroughGenericEventIsLogged(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(10);
        $user->method('getChanges')->willReturn(['username' => [null, 'john']]);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($this->callback(function (array $log): bool {
                $this->assertSame('user', $log['object']);
                $this->assertSame('create', $log['action']);
                $this->assertSame(10, $log['objectId']);
                $this->assertSame(['username' => [null, 'john']], $log['details']);
                return true;
            }));

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = $user;
        $subscriber->onEntityPostSave(new EntityPostSave($entity, true));
    }

    public function testRoleSaveThroughGenericEventIsLogged(): void
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(5);
        $role->method('getChanges')->willReturn(['name' => [null, 'Admin']]);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($this->callback(function (array $log): bool {
                $this->assertSame('role', $log['object']);
                $this->assertSame('create', $log['action']);
                $this->assertSame(5, $log['objectId']);
                return true;
            }));

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = $role;
        $subscriber->onEntityPostSave(new EntityPostSave($entity, true));
    }

    public function testUserDeleteThroughGenericEventIsLogged(): void
    {
        $user            = $this->createMock(User::class);
        $user->deletedId = 10;
        $user->method('getName')->willReturn('John Doe');

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($this->callback(function (array $log): bool {
                $this->assertSame('user', $log['object']);
                $this->assertSame('delete', $log['action']);
                $this->assertSame(10, $log['objectId']);
                $this->assertSame(['name' => 'John Doe'], $log['details']);
                return true;
            }));

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = $user;
        $subscriber->onEntityPostDelete(new EntityPostDelete($entity, false));
    }

    public function testRoleDeleteThroughGenericEventIsLogged(): void
    {
        $role            = $this->createMock(Role::class);
        $role->deletedId = 5;
        $role->method('getName')->willReturn('Admin');

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($this->callback(function (array $log): bool {
                $this->assertSame('role', $log['object']);
                $this->assertSame('delete', $log['action']);
                $this->assertSame(5, $log['objectId']);
                return true;
            }));

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = $role;
        $subscriber->onEntityPostDelete(new EntityPostDelete($entity, false));
    }

    public function testUnrelatedEntityIsIgnored(): void
    {
        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->never())
            ->method('writeToLog');

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = new \stdClass();
        $subscriber->onEntityPostSave(new EntityPostSave($entity, true));
    }

    public function testUserSaveWithoutChangesIsNotLogged(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getChanges')->willReturn([]);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->never())
            ->method('writeToLog');

        $subscriber = $this->createSubscriber($auditLogModel);
        $entity     = $user;
        $subscriber->onEntityPostSave(new EntityPostSave($entity, true));
    }

    public function testDedicatedUserSaveEventFromProviderIsLogged(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $user->method('getChanges')->willReturn(['username' => [null, 'sso']]);

        $auditLogModel = $this->createMock(AuditLogModel::class);
        $auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with($this->callback(function (array $log): bool {
                $this->assertSame('user', $log['object']);
                $this->assertSame('update', $log['action']);
                $this->assertSame(7, $log['objectId']);
                return true;
            }));

        $subscriber = $this->createSubscriber($auditLogModel);
        $subscriber->onUserPostSave(new PostSaveUserEvent($user, false));
    }

    private function createSubscriber(AuditLogModel $auditLogModel): UserSubscriber
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->method('getIpAddressFromRequest')->willReturn('127.0.0.1');

        return new UserSubscriber($ipLookupHelper, $auditLogModel);
    }
}
