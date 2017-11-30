<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

trait DefaultValueTrait
{
    /**
     * @param        $entity
     * @param string $object
     */
    protected function setEntityDefaultValues($entity, $object = 'lead')
    {
        if (!$entity->getId()) {
            // New contact so default values if not already set
            $updatedFields = $entity->getUpdatedFields();

            /** @var FieldModel $fieldModel */
            $fields = $this->leadFieldModel->getFieldListWithProperties($object);
            foreach ($fields as $alias => $field) {
                if (!isset($updatedFields[$alias]) && '' !== $field['defaultValue'] && null !== $field['defaultValue']) {
                    $entity->addUpdatedField($alias, $field['defaultValue']);
                }
            }
        }
    }
}
