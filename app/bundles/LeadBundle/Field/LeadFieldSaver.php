<?php

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

    /**
     * @param bool $isNew
     */
    public function saveLeadFieldEntity(LeadField $entity, $isNew)
    {
        try {
            $this->fieldSaveDispatcher->dispatchPreSaveEvent($entity, $isNew);
        } catch (NoListenerException $e) {
        }

        $this->leadFieldRepository->saveEntity($entity);

        try {
            $this->fieldSaveDispatcher->dispatchPostSaveEvent($entity, $isNew);
        } catch (NoListenerException $e) {
        }
    }
}
