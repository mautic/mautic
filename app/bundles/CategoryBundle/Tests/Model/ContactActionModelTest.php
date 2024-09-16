<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Tests\Model;

use Mautic\CategoryBundle\Model\ContactActionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;

class ContactActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Lead
     */
    private $contactMock5;

    /**
     * @var Lead
     */
    private $contactMock6;

    /**
     * @var MockObject|LeadModel
     */
    private $contactModelMock;

    /**
     * @var ContactActionModel
     */
    private $actionModel;

    protected function setUp(): void
    {
        $this->contactMock5     = new Lead();
        $this->contactMock6     = new Lead();
        $this->contactModelMock = $this->createMock(LeadModel::class);
        $this->actionModel      = new ContactActionModel($this->contactModelMock);
    }

    public function testAddContactsToCategoriesEntityAccess()
    {
        $contacts   = [5, 6];
        $categories = [4, 5];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->contactModelMock->expects($this->once())
            ->method('addToCategory')
            ->with($this->contactMock6);

        $this->actionModel->addContactsToCategories($contacts, $categories);
    }

    public function testRemoveContactsFromCategoriesEntityAccess()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->contactModelMock->expects($this->once())
            ->method('getLeadCategories')
            ->with($this->contactMock6)
            ->willReturn([45, 2]);

        $this->contactModelMock->expects($this->once())
            ->method('removeFromCategories')
            ->with([1 => 2]);

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }

    public function testAddContactsToCategories()
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturn(true);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('addToCategory')
            ->withConsecutive([$this->contactMock5, $categories], [$this->contactMock6, $categories]);

        $this->actionModel->addContactsToCategories($contacts, $categories);
    }

    public function testRemoveContactsFromCategories(): void
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturn(true);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('getLeadCategories')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturnOnConsecutiveCalls([1, 2], [2, 3]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('removeFromCategories')
            ->withConsecutive([$categories], [[2]]);

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }
}
