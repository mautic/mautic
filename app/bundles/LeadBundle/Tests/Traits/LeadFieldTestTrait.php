<?php

namespace Mautic\LeadBundle\Tests\Traits;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

trait LeadFieldTestTrait
{
    /**
     * @param array<mixed> $fieldDetails
     */
    protected function createField(array $fieldDetails): void
    {
        $field = new LeadField();
        $field->setLabel($fieldDetails['label'] ?? $fieldDetails['alias']);
        $field->setType($fieldDetails['type']);
        $field->setObject($fieldDetails['object'] ?? 'lead');
        $field->setGroup($fieldDetails['group'] ?? 'core');
        $field->setAlias($fieldDetails['alias']);
        $field->setIsPublished($fieldDetails['isPublished'] ?? true);

        if (isset($fieldDetails['properties'])) {
            $field->setProperties($fieldDetails['properties']);
        }

        $fieldModel = self::getContainer()->get('mautic.lead.model.field');
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);
    }
}
