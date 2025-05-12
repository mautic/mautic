<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Tests\Model;

use Mautic\CategoryBundle\Model\ContactActionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;

class ContactActionModelTest extends \PHPUnit\Framework\TestCase
{
    private Lead $contactMock5;

    private Lead $contactMock6;

    /**
     * @var MockObject|LeadModel
     */
    private MockObject $contactModelMock;

    private ContactActionModel $actionModel;

    protected function setUp(): void
    {
        $this->contactMock5     = new Lead();
        $this->contactMock6     = new Lead();
        $this->contactModelMock = $this->createMock(LeadModel::class);
        $this->actionModel      = new ContactActionModel($this->contactModelMock);
    }

    public function testAddContactsToCategoriesEntityAccess(): void
    {
        $contacts   = [5, 6];
        $categories = [4, 5];

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
            ->method('addToCategory')
            ->with($this->contactMock6);

        $this->actionModel->addContactsToCategories($contacts, $categories);
    }

    public function testRemoveContactsFromCategoriesEntityAccess(): void
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

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
            ->method('getLeadCategories')
            ->with($this->contactMock6)
            ->willReturn([45, 2]);

        $this->contactModelMock->expects($this->once())
            ->method('removeFromCategories')
            ->with([1 => 2]);

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }

    public function testAddContactsToCategories(): void
    {
        $contacts   = [5, 6];
        $categories = [1, 2];

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
            ->method('addToCategory')->willReturnCallback(function (...$parameters) use ($matcher, $categories) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);
                    $this->assertSame($categories, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);
                    $this->assertSame($categories, $parameters[1]);
                }

                return $categories;
            });

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
            ->method('getLeadCategories')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock5, $parameters[0]);

                    return [1, 2];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->contactMock6, $parameters[0]);

                    return [2, 3];
                }
            });
        $matcher = $this->exactly(2);

        $this->contactModelMock->expects($matcher)
            ->method('removeFromCategories')->willReturnCallback(function (...$parameters) use ($matcher, $categories) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($categories, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame([2], $parameters[0]);
                }
            });

        $this->actionModel->removeContactsFromCategories($contacts, $categories);
    }
}
