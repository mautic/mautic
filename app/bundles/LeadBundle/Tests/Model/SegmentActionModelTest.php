<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\SegmentActionModel;

class SegmentActionModelTest extends \PHPUnit\Framework\TestCase
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
     * @var SegmentActionModel
     */
    private $actionModel;

    protected function setUp()
    {
        $this->contactMock5        = $this->createMock(Lead::class);
        $this->contactMock6        = $this->createMock(Lead::class);
        $this->contactModelMock    = $this->createMock(LeadModel::class);
        $this->actionModel         = new SegmentActionModel($this->contactModelMock);
    }

    public function testAddContactsToSegmentsEntityAccess()
    {
        $contacts = [5, 6];
        $segments = [4, 5];

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
            ->method('addToLists')
            ->with($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->at(4))
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->addContacts($contacts, $segments);
    }

    public function testRemoveContactsFromSementsEntityAccess()
    {
        $contacts = [5, 6];
        $segments = [1, 2];

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
            ->method('removeFromLists')
            ->with($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->at(4))
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->removeContacts($contacts, $segments);
    }

    public function testAddContactsToSegments()
    {
        $contacts = [5, 6];
        $segments = [1, 2];

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
            ->method('addToLists')
            ->with($this->contactMock5, $segments);

        // Loop 2
        $this->contactModelMock->expects($this->at(3))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(4))
            ->method('addToLists')
            ->with($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->at(5))
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->addContacts($contacts, $segments);
    }

    public function testRemoveContactsFromCategories()
    {
        $contacts = [5, 6];
        $segments = [1, 2];

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
            ->method('removeFromLists')
            ->with($this->contactMock5, $segments);

        // Loop 2
        $this->contactModelMock->expects($this->at(3))
            ->method('canEditContact')
            ->with($this->contactMock6)
            ->willReturn(true);

        $this->contactModelMock->expects($this->at(4))
            ->method('removeFromLists')
            ->with($this->contactMock6)
            ->willReturn($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->at(5))
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->removeContacts($contacts, $segments);
    }
}
