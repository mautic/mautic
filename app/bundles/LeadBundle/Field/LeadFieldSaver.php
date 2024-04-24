<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;

class LeadFieldSaver
{
    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
        private FieldSaveDispatcher $fieldSaveDispatcher
    ) {
    }

    public function saveLeadFieldEntity(LeadField $leadField, bool $isNew): void
    {
        try {
            $this->fieldSaveDispatcher->dispatchPreSaveEvent($leadField, $isNew);
        } catch (NoListenerException) {
        }

        $this->leadFieldRepository->saveEntity($leadField);

        try {
            $this->fieldSaveDispatcher->dispatchPostSaveEvent($leadField, $isNew);
        } catch (NoListenerException) {
        }
    }

    public function saveLeadFieldEntityWithoutColumnCreated(LeadField $leadField): void
    {
        $leadField->setColumnIsNotCreated();

        $this->saveLeadFieldEntity($leadField, true);
    }
}
