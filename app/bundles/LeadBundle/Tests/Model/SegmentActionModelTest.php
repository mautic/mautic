<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\SegmentActionModel;

class SegmentActionModelTest extends \PHPUnit\Framework\TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $contactMock5;

    private \PHPUnit\Framework\MockObject\MockObject $contactMock6;

    private \PHPUnit\Framework\MockObject\MockObject $contactModelMock;

    private SegmentActionModel $actionModel;

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
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('canEditContact')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);

                    return false;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);

                    return true;
                }
            });

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
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('canEditContact')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);

                    return false;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);

                    return true;
                }
            });

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
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('canEditContact')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);
                }

                return true;
            });
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('addToLists')->willReturnCallback(function (...$parameters) use ($matcher, $segments) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);
                    $this->assertSame($segments, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);
                    $this->assertSame($segments, $parameters[1]);
                }
            });

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
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('canEditContact')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);
                }

                return true;
            });
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('removeFromLists')->willReturnCallback(function (...$parameters) use ($matcher, $segments) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);
                    $this->assertSame($segments, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);
                }
            });

        $this->contactModelMock->expects($this->once())
            ->method('saveEntities')
            ->with([$this->contactMock5, $this->contactMock6]);

        $this->actionModel->removeContacts($contacts, $segments);
    }
}
