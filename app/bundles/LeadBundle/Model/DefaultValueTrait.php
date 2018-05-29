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
