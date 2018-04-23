<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Tests\Model;

use Mautic\CategoryBundle\Model\ContactActionModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class ContactActionModelTest extends \PHPUnit_Framework_TestCase
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
    private $contactModelMock;

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
        $this->userMock1 = $this->createMock(User::class);
        $this->userMock2 = $this->createMock(User::class);
        $this->leadMock5 = $this->createMock(Lead::class);
        $this->leadMock6 = $this->createMock(Lead::class);

        $this->contactModelMock   = $this->createMock(LeadModel::class);
        $this->corePermissionMock = $this->createMock(CorePermissions::class);

        $this->leadMock5->method('getPermissionUser')->willReturn($this->userMock1);
        $this->leadMock6->method('getPermissionUser')->willReturn($this->userMock2);

        $this->contactModelMock->expects($this->any())
            ->method('getEntities')->with([
                'filter' => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'in',
                            'value'  => [5, 6],
                        ],
                    ],
                ],
            ])->willReturn([$this->leadMock5, $this->leadMock6]);

        $this->model = new ContactActionModel(
            $this->contactModelMock,
            $this->corePermissionMock
        );
    }

    public function testAddContactsToCategoriesEntityAccess()
    {
        $toAdd = [4, 5];

        $this->corePermissionMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(false);

        $this->corePermissionMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->contactModelMock->expects($this->any())
            ->method('getLeadCategories')
            ->with($this->leadMock5)
            ->willThrowException(new \Exception('Get categories has been called!'));

        $this->model->addContactsToCategories([5, 6], $toAdd);
    }

    public function testRemoveContactsFromCategoriesEntityAccess()
    {
        $toRemove = [1, 2];

        $this->corePermissionMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(false);

        $this->corePermissionMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->contactModelMock->expects($this->any())
            ->method('getLeadCategories')
            ->with($this->leadMock5)
            ->willThrowException(new \Exception('Get categories has been called!'));

        $this->model->removeContactsFromCategories([5, 6], $toRemove);
    }

    public function testRemoveContactsFromCategories()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        // Loop 1
        $this->contactModelMock->expects($this->at(1))
            ->method('getLeadCategories')
            ->with($this->leadMock5)
            ->willReturn([1, 2]);

        $this->corePermissionMock->expects($this->at(0))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock1)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(2))
            ->method('removeFromCategories')
            ->with($categories);

        $this->contactModelMock->expects($this->at(3))
            ->method('detachEntity')
            ->with($this->leadMock5);

        // Loop 2
        $this->contactModelMock->expects($this->at(4))
            ->method('getLeadCategories')
            ->with($this->leadMock6)
            ->willReturn([2, 3]);

        $this->corePermissionMock->expects($this->at(1))
            ->method('hasEntityAccess')
            ->with('lead:leads:editown', 'lead:leads:editother', $this->userMock2)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(5))
            ->method('removeFromCategories')
            ->with([2]);

        $this->contactModelMock->expects($this->at(6))
            ->method('detachEntity')
            ->with($this->leadMock5);

        $this->model->removeContactsFromCategories($contacts, $categories);
    }
}
