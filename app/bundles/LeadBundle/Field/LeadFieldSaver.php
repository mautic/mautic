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

namespace Mautic\LeadBundle\Field;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;

class LeadFieldSaver
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var FieldSaveDispatcher
     */
    private $fieldSaveDispatcher;

    public function __construct(LeadFieldRepository $leadFieldRepository, FieldSaveDispatcher $fieldSaveDispatcher)
    {
        $this->leadFieldRepository = $leadFieldRepository;
        $this->fieldSaveDispatcher = $fieldSaveDispatcher;
    }

    public function saveLeadFieldEntity(LeadField $leadField, bool $isNew): void
    {
        try {
            $this->fieldSaveDispatcher->dispatchPreSaveEvent($leadField, $isNew);
        } catch (NoListenerException $e) {
        }

        $this->leadFieldRepository->saveEntity($leadField);

        try {
            $this->fieldSaveDispatcher->dispatchPostSaveEvent($leadField, $isNew);
        } catch (NoListenerException $e) {
        }
    }

    public function saveLeadFieldEntityWithoutColumnCreated(LeadField $leadField): void
    {
        $leadField->setColumnIsNotCreated();

        $this->saveLeadFieldEntity($leadField, true);
    }
}
