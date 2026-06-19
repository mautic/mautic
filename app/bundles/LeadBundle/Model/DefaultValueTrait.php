<?php

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Entity\CustomFieldEntityInterface;

trait DefaultValueTrait
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $cachedDefaultFields = [];

    /**
     * @param string $object
     */
    protected function setEntityDefaultValues(CustomFieldEntityInterface $entity, $object = 'lead')
    {
        if ($entity->getId()) {
            return;
        }

        if (!isset($this->cachedDefaultFields[$object])) {
            $this->cachedDefaultFields[$object] = $this->leadFieldModel->getFieldListWithProperties($object);
        }

        foreach ($this->cachedDefaultFields[$object] as $alias => $field) {
            // Prevent defaults from overwriting values already set
            $value = $entity->getFieldValue($alias);

            if ((null === $value || '' === $value) && '' !== $field['defaultValue'] && null !== $field['defaultValue']) {
                $entity->addUpdatedField($alias, $field['defaultValue']);
            }
        }
    }
}
