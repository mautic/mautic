<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldDeleteDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;

class LeadFieldDeleter
{
    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
        private FieldDeleteDispatcher $fieldDeleteDispatcher,
        private UserHelper $userHelper,
    ) {
    }

    /**
     * @param bool $isBackground - if processing in background
     */
    public function deleteLeadFieldEntity(LeadField $leadField, bool $isBackground = false): void
    {
        try {
            $this->fieldDeleteDispatcher->dispatchPreDeleteEvent($leadField);
        } catch (NoListenerException) {
        } catch (AbortColumnUpdateException) { // if processing in background is ON
            if (!$isBackground) {
                $this->deleteLeadFieldEntityWithoutColumnRemoved($leadField);

                return;
            }
        }

        $leadField->deletedId = $leadField->getId();
        $this->leadFieldRepository->deleteEntity($leadField);

        try {
            $this->fieldDeleteDispatcher->dispatchPostDeleteEvent($leadField);
        } catch (NoListenerException) {
        }
    }

    /**
     * Marks the field for delation in the background and sets the modified by user who
     * will be used as the user who will actually delete the field in the background.
     * Such soft-deleted field will disappear from the UI.
     *
     * Note: The LeadModel would set most of this for us, but cannot be used due to circular dependency.
     */
    private function deleteLeadFieldEntityWithoutColumnRemoved(LeadField $leadField): void
    {
        $currentUser = $this->userHelper->getUser();
        $leadField->setColumnIsNotRemoved();
        $leadField->setModifiedBy($currentUser);
        $leadField->setModifiedByUser($currentUser?->getName());
        $leadField->setDateModified((new DateTimeHelper())->getDateTime());
        $leadField->setIsPublished(false);

        $this->leadFieldRepository->saveEntity($leadField);
    }
}
