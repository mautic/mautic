<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommonStatsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $security;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $statsEvent;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $subscirber;

    protected function setUp()
    {
        parent::setUp();
        $this->security   = $this->createMock(CorePermissions::class);
        $this->user       = $this->createMock(User::class);
        $this->repository = $this->createMock(CommonRepository::class);
        $this->statsEvent = $this->createMock(StatsEvent::class);
        $this->subscirber = $this->getMockForAbstractClass(CommonStatsSubscriber::class);
        $this->subscirber->setSecurity($this->security);
    }

    public function testOnStatsFetchForRestrictedUsers()
    {
        $this->setProperty($this->subscirber, 'repositories', [$this->repository]);
        $this->setProperty($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(9);

        $this->security->expects($this->at(0))
            ->method('checkPermissionExists')
            ->with('lead:leads:view')
            ->willReturn(true);

        $this->security->expects($this->at(1))
            ->method('isGranted')
            ->with('lead:leads:view')
            ->willReturn(false);

        $this->security->expects($this->at(2))
            ->method('checkPermissionExists')
            ->with('lead:leads:viewother')
            ->willReturn(true);

        $this->security->expects($this->at(3))
            ->method('isGranted')
            ->with('lead:leads:viewother')
            ->willReturn(true);

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

    public function testOnStatsFetchForViewAllUsers()
    {
        $this->setProperty($this->subscirber, 'repositories', [$this->repository]);
        $this->setProperty($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->security->expects($this->at(0))
            ->method('checkPermissionExists')
            ->with('lead:leads:view')
            ->willReturn(true);

        $this->security->expects($this->at(1))
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

    public function testOnStatsFetchForAdminUsers()
    {
        $this->setProperty($this->subscirber, 'repositories', [$this->repository]);
        $this->setProperty($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'admin']]);

        $this->security->expects($this->at(0))
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

    public function testOnStatsFetchForNoPermissionUsers()
    {
        $this->setProperty($this->subscirber, 'repositories', [$this->repository]);
        $this->setProperty($this->subscirber, 'permissions', ['emails_stats' => ['lead' => 'lead:leads']]);

        $this->repository->expects($this->once())
            ->method('getTableName')
            ->willReturn('emails_stats');

        $this->security->expects($this->at(0))
            ->method('checkPermissionExists')
            ->with('lead:leads:view')
            ->willReturn(true);

        $this->security->expects($this->at(1))
            ->method('isGranted')
            ->with('lead:leads:view')
            ->willReturn(false);

        $this->security->expects($this->at(2))
            ->method('checkPermissionExists')
            ->with('lead:leads:viewother')
            ->willReturn(true);

        $this->security->expects($this->at(3))
            ->method('isGranted')
            ->with('lead:leads:viewother')
            ->willReturn(false);

        $this->statsEvent->expects($this->once())
            ->method('isLookingForTable')
            ->with('emails_stats', $this->repository)
            ->willReturn(true);

        $this->statsEvent->expects($this->never())
            ->method('setSelect');

        $this->expectException(AccessDeniedException::class);
        $this->subscirber->onStatsFetch($this->statsEvent);
    }

    private function setProperty($object, $property, $value)
    {
        $reflection         = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}
