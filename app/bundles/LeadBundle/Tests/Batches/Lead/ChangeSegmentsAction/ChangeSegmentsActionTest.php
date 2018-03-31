<?php

namespace Mautic\LeadBundle\Tests\Batches\Lead\ChangeSegmentsAction;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Batches\Lead\ChangeSegmentsAction\ChangeSegmentsAction;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class ChangeSegmentsActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadMock5;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadMock6;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $corePermissionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userMock1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userMock2;

    protected function setUp()
    {
        $this->leadMock5 = $this->createMock(Lead::class);
        $this->leadMock6 = $this->createMock(Lead::class);

        $this->userMock1 = $this->createMock(User::class);
        $this->userMock2 = $this->createMock(User::class);

        $this->leadModelMock      = $this->createMock(LeadModel::class);
        $this->corePermissionMock = $this->createMock(CorePermissions::class);

        $this->leadMock5->method('getPermissionUser')->willReturn($this->userMock1);
        $this->leadMock6->method('getPermissionUser')->willReturn($this->userMock2);

        $this->leadModelMock->expects($this->any())->method('getEntities')->with([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => [5, 6],
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ])->willReturn([$this->leadMock5, $this->leadMock6]);
    }

    public function testExecuteEntityAccess()
    {
        $this->setUp();
        $toAdd    = [4, 5];
        $toRemove = [1, 2];

        $this->corePermissionMock->expects($this->at(0))->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(false);

        $this->corePermissionMock->expects($this->at(0))->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->leadModelMock->expects($this->any())->method('addToLists')
            ->with($this->leadMock5, $toAdd)
            ->willThrowException(new \Exception('Add to list has been called!'));

        $this->leadModelMock->expects($this->any())->method('removeFromLists')
            ->with($this->leadMock5, $toAdd)
            ->willThrowException(new \Exception('Add to list has been called!'));

        $this->execute([5, 6], $toAdd, $toRemove);
    }

    public function testExecuteEmptySegmentsToAdd()
    {
        $this->setUp();
        $toAdd    = [];
        $toRemove = [];

        $this->corePermissionMock->expects($this->any())->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(true);

        $this->corePermissionMock->expects($this->any())->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->leadModelMock->expects($this->any())->method('addToLists')
            ->with($this->leadMock5, $toAdd)
            ->willThrowException(new \Exception('Nothing to add'));

        $this->leadModelMock->expects($this->any())->method('removeFromLists')
            ->with($this->leadMock5, $toRemove)
            ->willThrowException(new \Exception('Nothing to remove'));

        $this->execute([5, 6], $toAdd, $toRemove);
    }

    private function execute($leads = [], $segmentsToAdd = [], $segmentsToRemove = [])
    {
        $action = new ChangeSegmentsAction(
            $this->leadModelMock,
            $this->corePermissionMock,
            $leads,
            $segmentsToAdd,
            $segmentsToRemove
        );

        $action->execute();
    }
}
