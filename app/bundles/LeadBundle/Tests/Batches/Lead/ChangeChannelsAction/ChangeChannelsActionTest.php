<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Batches\Lead\ChangeChannelsAction;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Batches\Lead\ChangeChannelsAction\ChangeChannelsAction;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class ChangeChannelsActionTest extends \PHPUnit_Framework_TestCase
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
    private $doNotContactMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $frequencyRuleRepositoryMock;

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

        $this->leadModelMock                 = $this->createMock(LeadModel::class);
        $this->corePermissionMock            = $this->createMock(CorePermissions::class);
        $this->doNotContactMock              = $this->createMock(DoNotContact::class);
        $this->frequencyRuleRepositoryMock   = $this->createMock(FrequencyRuleRepository::class);

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

        $this->corePermissionMock->expects($this->at(0))->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(false);

        $this->corePermissionMock->expects($this->at(0))->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->leadModelMock->expects($this->any())->method('getContactChannels')
            ->with($this->leadMock5)
            ->willThrowException(new \Exception('Get contact channels has been called!'));

        $this->leadModelMock->expects($this->any())->method('getPreferenceChannels')
            ->with($this->leadMock5)
            ->willThrowException(new \Exception('Get preference channels has been called!'));

        $this->execute([5, 6]);
    }

    private function execute($leads = [])
    {
        $action = new ChangeChannelsAction(
            $leads,
            ['Email' => 'email'],
            [],
            $this->leadModelMock,
            $this->corePermissionMock,
            $this->doNotContactMock,
            $this->frequencyRuleRepositoryMock,
            null
        );

        $action->execute();
    }
}
