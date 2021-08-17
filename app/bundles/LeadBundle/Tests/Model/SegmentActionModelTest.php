<?php

declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;

class SegmentActionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $contactMock5;

    /**
     * @var MockObject
     */
    private $contactMock6;

    /**
     * @var MockObject
     */
    private $contactModelMock;

    /**
     * @var SegmentActionModel
     */
    private $actionModel;

    protected function setUp(): void
    {
        $this->contactMock5        = $this->createMock(Lead::class);
        $this->contactMock6        = $this->createMock(Lead::class);
        $this->contactModelMock    = $this->createMock(LeadModel::class);
        $this->actionModel         = new SegmentActionModel($this->contactModelMock);
    }

    public function testAddContactsToSegmentsEntityAccess(): void
    {
        $contacts = [5, 6];
        $segments = [4, 5];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->contactModelMock->expects($this->once())
            ->method('addToLists')
            ->with($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->once())
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->addContacts($contacts, $segments);
    }

    public function testRemoveContactsFromSementsEntityAccess(): void
    {
        $contacts = [5, 6];
        $segments = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->contactModelMock->expects($this->once())
            ->method('removeFromLists')
            ->with($this->contactMock6, $segments);

        $this->contactModelMock->expects($this->once())
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->removeContacts($contacts, $segments);
    }

    public function testAddContactsToSegments(): void
    {
        $contacts = [5, 6];
        $segments = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturn(true);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('addToLists')
            ->withConsecutive([$this->contactMock5, $segments], [$this->contactMock6, $segments]);

        $this->contactModelMock->expects($this->once())
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->addContacts($contacts, $segments);
    }

    public function testRemoveContactsFromCategories(): void
    {
        $contacts = [5, 6];
        $segments = [1, 2];

        $this->contactModelMock->expects($this->once())
            ->method('getLeadsByIds')
            ->with($contacts)
            ->willReturn([$this->contactMock5, $this->contactMock6]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('canEditContact')
            ->withConsecutive([$this->contactMock5], [$this->contactMock6])
            ->willReturn(true);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('removeFromLists')
            ->withConsecutive([$this->contactMock5, $segments], [$this->contactMock6]);

        $this->contactModelMock->expects($this->once())
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->removeContacts($contacts, $segments);
    }
}
