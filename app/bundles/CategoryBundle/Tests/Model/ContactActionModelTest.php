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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock5;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock6;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contactModelMock;

    /**
     * @var ContactActionModel
     */
    private $actionModel;

    protected function setUp()
    {
        $this->contactMock5     = $this->createMock(Lead::class);
        $this->contactMock6     = $this->createMock(Lead::class);
        $this->contactModelMock = $this->createMock(LeadModel::class);
        $this->actionModel      = new ContactActionModel($this->contactModelMock);
    }

    public function testAddContactsToCategoriesEntityAccess()
    {
        $contacts   = [5, 6];
        $categories = [4, 5];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(false);

        $this->contactModelMock->expects($this->at(2))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(3))
            ->method('addToCategory')
            ->with($this->contactMock6);

        $this->actionModel->addContactsToCategories($contacts, $categories);
    }

    public function testRemoveContactsFromCategoriesEntityAccess()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(false);

        $this->contactModelMock->expects($this->at(2))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(3))
            ->method('getLeadCategories')
            ->with($this->contactMock6)
            ->willReturn([45, 2]);

        $this->contactModelMock->expects($this->at(4))
            ->method('removeFromCategories')
            ->with([1 => 2]);

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }

    public function testAddContactsToCategories()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        // Loop 1
        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(2))
            ->method('addToCategory')
            ->with($this->contactMock5, $categories);

        // Loop 2
        $this->contactModelMock->expects($this->at(3))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(4))
            ->method('addToCategory')
            ->with($this->contactMock6, $categories);

        $this->actionModel->addContactsToCategories($contacts, $categories);
    }

    public function testRemoveContactsFromCategories()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->at(0))
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        // Loop 1
        $this->contactModelMock->expects($this->at(1))
            ->method('canEditContact')
            ->with($this->contactMock5)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(2))
            ->method('getLeadCategories')
            ->with($this->contactMock5)
            ->willReturn([1, 2]);

        $this->contactModelMock->expects($this->at(3))
            ->method('removeFromCategories')
            ->with($categories);

        // Loop 2
        $this->contactModelMock->expects($this->at(4))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(5))
            ->method('getLeadCategories')
            ->with($this->contactMock6)
            ->willReturn([2, 3]);

        $this->contactModelMock->expects($this->at(6))
            ->method('removeFromCategories')
            ->with([2]);

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }
}
