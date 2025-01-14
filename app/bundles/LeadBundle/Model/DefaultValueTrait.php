<?php

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Entity\CustomFieldEntityInterface;

trait DefaultValueTrait
{
    /**
     * @param        $entity
     * @param string $object
     */
    protected function setEntityDefaultValues(CustomFieldEntityInterface $entity, $object = 'lead')
    {
        if (!$entity->getId()) {
            /** @var FieldModel $fieldModel */
            $fields = $this->leadFieldModel->getFieldListWithProperties($object);
            foreach ($fields as $alias => $field) {
                // Prevent defaults from overwriting values already set
                $value = $entity->getFieldValue($alias);

                if ((null === $value || '' === $value) && '' !== $field['defaultValue'] && null !== $field['defaultValue']) {
                    $entity->addUpdatedField($alias, $field['defaultValue']);
                }
            }
        }
    }
}
