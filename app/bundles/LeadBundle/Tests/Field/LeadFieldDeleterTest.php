<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldDeleteDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\LeadFieldDeleter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LeadFieldDeleterTest extends TestCase
{
    /**
     * @var MockObject&LeadFieldRepository
     */
    private MockObject $leadFieldRepositoryMock;

    /**
     * @var MockObject&FieldDeleteDispatcher
     */
    private MockObject $fieldDeleteDispatcherMock;

    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelperMock;

    private LeadFieldDeleter $leadFieldDeleter;

    protected function setUp(): void
    {
        $this->leadFieldRepositoryMock   = $this->createMock(LeadFieldRepository::class);
        $this->fieldDeleteDispatcherMock = $this->createMock(FieldDeleteDispatcher::class);
        $this->userHelperMock            = $this->createMock(UserHelper::class);
        $this->leadFieldDeleter          = new LeadFieldDeleter(
            $this->leadFieldRepositoryMock,
            $this->fieldDeleteDispatcherMock,
            $this->userHelperMock,
        );
    }

    public function testDeleteLeadFieldEntityNoBackground(): void
    {
        $leadField = new LeadField();
        $this->fieldDeleteDispatcherMock
            ->expects($this->once())
            ->method('dispatchPreDeleteEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnUpdateException());
        $this->leadFieldRepositoryMock
            ->expects($this->never())
            ->method('deleteEntity');
        $this->leadFieldDeleter->deleteLeadFieldEntity($leadField, false);
    }

    public function testDeleteLeadFieldEntityInBackground(): void
    {
        $leadField = new LeadField();
        $this->fieldDeleteDispatcherMock
            ->expects($this->once())
            ->method('dispatchPreDeleteEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnUpdateException());
        $this->leadFieldRepositoryMock
            ->expects($this->once())
            ->method('deleteEntity')
            ->with($leadField);
        $this->fieldDeleteDispatcherMock
            ->expects($this->once())
            ->method('dispatchPostDeleteEvent')
            ->with($leadField)
            ->willThrowException(new NoListenerException());
        $this->leadFieldDeleter->deleteLeadFieldEntity($leadField, true);
    }
}
