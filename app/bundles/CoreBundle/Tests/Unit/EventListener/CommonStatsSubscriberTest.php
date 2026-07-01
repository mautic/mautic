<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CommonStatsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $security;

    /**
     * @var MockObject&User
     */
    private MockObject $user;

    /**
     * @var MockObject&CommonRepository
     */
    private MockObject $repository;

    /**
     * @var MockObject&StatsEvent
     */
    private MockObject $statsEvent;

    /**
     * @var CommonStatsSubscriber|MockObject
     */
    private MockObject $subscirber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->security      = $this->createMock(CorePermissions::class);
        $entityManager       = $this->createMock(EntityManager::class);
        $this->user          = $this->createMock(User::class);
        $this->repository    = $this->createMock(CommonRepository::class);
        $this->statsEvent    = $this->createMock(StatsEvent::class);
        $this->subscirber    = $this->getMockBuilder(CommonStatsSubscriber::class)
            ->setConstructorArgs(
                [
                    $this->security,
                    $entityManager,
                ]
            )
            ->onlyMethods([])
            ->getMock();
    }

    public function testOnStatsFetchForRestrictedUsers(): void
    {
        ReflectionHelper::setValue($this->subscirber, 'repositories', [$this->repository]);
        ReflectionHelper::setValue($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(9);
        $matcher = $this->exactly(2);

        $this->security->expects($matcher)
            ->method('checkPermissionExists')->willReturnCallback(function (...$parameters) use ($matcher): true {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:view', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:viewother', $parameters[0]);
                }

                return true;
            });
        $matcher = $this->exactly(2);

        $this->security->expects($matcher)
            ->method('isGranted')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:view', $parameters[0]);

                    return false;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:viewother', $parameters[0]);

                    return true;
                }
            });

        $this->repository->expects($this->once())
            ->method('getTableName')
            ->willReturn('emails_stats');

        $this->statsEvent->expects($this->once())
            ->method('isLookingForTable')
            ->with('emails_stats', $this->repository)
            ->willReturn(true);

        $this->statsEvent->expects($this->once())
            ->method('addWhere')
            ->with([
                'internal' => true,
                'expr'     => 'formula',
                'value'    => 'IF (lead.owner_id IS NOT NULL, lead.owner_id, lead.created_by) = 9',
            ]);

        $this->statsEvent->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->statsEvent->expects($this->once())
            ->method('setRepository')
            ->with($this->repository, ['lead']);

        $this->statsEvent->expects($this->once())
            ->method('setSelect')
            ->willReturnSelf();

        $this->subscirber->onStatsFetch($this->statsEvent);
    }

    public function testOnStatsFetchForViewAllUsers(): void
    {
        ReflectionHelper::setValue($this->subscirber, 'repositories', [$this->repository]);
        ReflectionHelper::setValue($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->security->expects($this->once())
            ->method('checkPermissionExists')
            ->with('lead:leads:view')
            ->willReturn(true);

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('lead:leads:view')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('getTableName')
            ->willReturn('emails_stats');

        $this->statsEvent->expects($this->once())
            ->method('isLookingForTable')
            ->with('emails_stats', $this->repository)
            ->willReturn(true);

        $this->statsEvent->expects($this->once())
            ->method('setRepository')
            ->with($this->repository, []);

        $this->statsEvent->expects($this->once())
            ->method('setSelect')
            ->willReturnSelf();

        $this->subscirber->onStatsFetch($this->statsEvent);
    }

    public function testOnStatsFetchForAdminUsers(): void
    {
        ReflectionHelper::setValue($this->subscirber, 'repositories', [$this->repository]);
        ReflectionHelper::setValue($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'admin']]);

        $this->security->expects($this->once())
            ->method('isAdmin')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('getTableName')
            ->willReturn('emails_stats');

        $this->statsEvent->expects($this->once())
            ->method('isLookingForTable')
            ->with('emails_stats', $this->repository)
            ->willReturn(true);

        $this->statsEvent->expects($this->once())
            ->method('setSelect')
            ->willReturnSelf();

        $this->subscirber->onStatsFetch($this->statsEvent);
    }

    public function testOnStatsFetchForNoPermissionUsers(): void
    {
        ReflectionHelper::setValue($this->subscirber, 'repositories', [$this->repository]);
        ReflectionHelper::setValue($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->repository->expects($this->once())
            ->method('getTableName')
            ->willReturn('emails_stats');
        $matcher = $this->exactly(2);

        $this->security->expects($matcher)
            ->method('checkPermissionExists')->willReturnCallback(function (...$parameters) use ($matcher): true {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:view', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:viewother', $parameters[0]);
                }

                return true;
            });
        $matcher = $this->exactly(2);

        $this->security->expects($matcher)
            ->method('isGranted')->willReturnCallback(function (...$parameters) use ($matcher): false {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:view', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead:leads:viewother', $parameters[0]);
                }

                return false;
            });

        $this->statsEvent->expects($this->once())
            ->method('isLookingForTable')
            ->with('emails_stats', $this->repository)
            ->willReturn(true);

        $this->statsEvent->expects($this->never())
            ->method('setSelect');

        $this->expectException(AccessDeniedException::class);
        $this->subscirber->onStatsFetch($this->statsEvent);
    }
}
