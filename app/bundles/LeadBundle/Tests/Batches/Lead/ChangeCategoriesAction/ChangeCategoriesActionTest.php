<?php

namespace Mautic\LeadBundle\Tests\Batches\Lead\ChangeCategoriesAction;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Batches\Lead\ChangeCategoriesAction\ChangeCategoriesAction;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class ChangeCategoriesActionTest extends \PHPUnit_Framework_TestCase
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

        $this->leadModelMock->expects($this->any())->method('getLeadCategories')
            ->with($this->leadMock5)
            ->willThrowException(new \Exception('Get categories has been called!'));

        $this->execute([5, 6], $toAdd, $toRemove);
    }

    public function testExecuteRemoveCategories()
    {
        $this->setUp();
        $toAdd    = [];
        $toRemove = [1, 2];

        $this->leadModelMock->expects($this->any())->method('getLeadCategories')
            ->with($this->leadMock5)
            ->willReturn([1, 2]);

        $this->leadModelMock->expects($this->any())->method('getLeadCategories')
            ->with($this->leadMock6)
            ->willReturn([2, 3]);

        $this->leadModelMock->expects($this->any())->method('removeFromCategories')
            ->with($toRemove)
            ->willThrowException(new \Exception('Nothing to remove!'));

        $this->execute([5, 6], $toAdd, $toRemove);
    }

    private function execute($leads = [], $categoriesToAdd = [], $categoriesToRemove = [])
    {
        $action = new ChangeCategoriesAction(
            $this->leadModelMock,
            $this->corePermissionMock,
            $leads,
            $categoriesToAdd,
            $categoriesToRemove
        );

        $action->execute();
    }
}
