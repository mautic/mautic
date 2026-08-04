<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;
use Mautic\LeadBundle\Field\LeadFieldSaver;

final class LeadFieldSaverTest extends \PHPUnit\Framework\TestCase
{
    public function testSave(): void
    {
        $leadFieldRepository = $this->createStub(LeadFieldRepository::class);
        $fieldSaveDispatcher = $this->createMock(FieldSaveDispatcher::class);

        $leadFieldSaver = new LeadFieldSaver($leadFieldRepository, $fieldSaveDispatcher);

        $leadField = new LeadField();

        $fieldSaveDispatcher->expects($this->once())
            ->method('dispatchPreSaveEvent')
            ->with($leadField, true)
            ->willReturn(new LeadFieldEvent($leadField));

        $fieldSaveDispatcher->expects($this->once())
            ->method('dispatchPostSaveEvent')
            ->with($leadField, true)
            ->willReturn(new LeadFieldEvent($leadField));

        $leadFieldSaver->saveLeadFieldEntity($leadField, true);
    }

    public function testSaveNoColumnCreated(): void
    {
        $leadFieldRepository = $this->createStub(LeadFieldRepository::class);
        $fieldSaveDispatcher = $this->createMock(FieldSaveDispatcher::class);

        $leadFieldSaver = new LeadFieldSaver($leadFieldRepository, $fieldSaveDispatcher);

        $leadField = new LeadField();

        $fieldSaveDispatcher->expects($this->once())
            ->method('dispatchPreSaveEvent')
            ->with($leadField, true)
            ->willReturn(new LeadFieldEvent($leadField));

        $fieldSaveDispatcher->expects($this->once())
            ->method('dispatchPostSaveEvent')
            ->with($leadField, true)
            ->willReturn(new LeadFieldEvent($leadField));

        $leadFieldSaver->saveLeadFieldEntityWithoutColumnCreated($leadField);

        $this->assertTrue($leadField->getColumnIsNotCreated());
    }
}
