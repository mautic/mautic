<?php

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\FieldModel;

trait DeduperTrait
{
    private $object = 'lead';

    /**
     * @var FieldModel
     */
    private $fieldModel;

    private FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier;

    /**
     * @var array
     */
    private $availableFields;

    public function getUniqueData(array $queryFields): array
    {
        $uniqueLeadFields    = $this->fieldsWithUniqueIdentifier->getFieldsWithUniqueIdentifier(['object' => $this->object]);
        $uniqueLeadFieldData = [];
        $inQuery             = array_intersect_key($queryFields, $this->getAvailableFields());
        foreach ($inQuery as $k => $v) {
            // Don't use empty values when checking for duplicates
            if (empty($v)) {
                continue;
            }

            if (array_key_exists($k, $uniqueLeadFields)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        return $uniqueLeadFieldData;
    }

    /**
     * @return array
     */
    private function getAvailableFields()
    {
        if (null === $this->availableFields) {
            $this->availableFields = $this->fieldModel->getFieldList(
                false,
                false,
                [
                    'isPublished' => true,
                    'object'      => $this->object,
                ]
            );
        }

        return $this->availableFields;
    }
}
