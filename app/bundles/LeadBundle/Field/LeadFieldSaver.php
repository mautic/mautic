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

class LeadFieldSaver
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var FieldDispatcher
     */
    private $fieldDispatcher;

    public function __construct(LeadFieldRepository $leadFieldRepository, FieldDispatcher $fieldDispatcher)
    {
        $this->leadFieldRepository = $leadFieldRepository;
        $this->fieldDispatcher     = $fieldDispatcher;
    }

    /**
     * @param bool $isNew
     */
    public function saveLeadFieldEntity(LeadField $entity, $isNew)
    {
        try {
            $this->fieldDispatcher->dispatchPreSaveEvent($entity, $isNew);
        } catch (NoListenerException $e) {
        }

        $this->leadFieldRepository->saveEntity($entity);

        try {
            $this->fieldDispatcher->dispatchPostSaveEvent($entity, $isNew);
        } catch (NoListenerException $e) {
        }
    }
}
