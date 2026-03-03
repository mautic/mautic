<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldDeleteDispatcher;
use Mautic\LeadBundle\Field\LeadFieldDeleter;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LeadFieldDeleterTest extends TestCase
{
    private MockObject&LeadFieldRepository $leadFieldRepositoryMock;
    private MockObject&FieldDeleteDispatcher $fieldDeleteDispatcherMock;
    private MockObject&BackgroundSettings $backgroundSettingsMock;
    private LeadFieldDeleter $leadFieldDeleter;

    protected function setUp(): void
    {
        $this->leadFieldRepositoryMock   = $this->createMock(LeadFieldRepository::class);
        $this->fieldDeleteDispatcherMock = $this->createMock(FieldDeleteDispatcher::class);
        $this->backgroundSettingsMock    = $this->createMock(BackgroundSettings::class);
        $this->leadFieldDeleter          = new LeadFieldDeleter(
            $this->leadFieldRepositoryMock,
            $this->fieldDeleteDispatcherMock,
            $this->createMock(UserHelper::class),
            $this->backgroundSettingsMock,
        );
    }

    public function testDeleteLeadFieldEntityNoBackground(): void
    {
        $leadField = new LeadField();
        $this->backgroundSettingsMock
            ->expects($this->once())
            ->method('shouldProcessColumnChangeInBackground')
            ->willReturn(true);
        $this->leadFieldRepositoryMock
            ->expects($this->never())
            ->method('deleteEntity');
        $this->leadFieldDeleter->deleteLeadFieldEntity($leadField);
    }

    public function testDeleteLeadFieldEntityInBackground(): void
    {
        $leadField = new LeadField();
        $this->backgroundSettingsMock
            ->expects($this->once())
            ->method('shouldProcessColumnChangeInBackground')
            ->willReturn(true);
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
