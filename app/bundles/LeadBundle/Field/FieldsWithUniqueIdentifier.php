<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

class FieldsWithUniqueIdentifier
{
    /**
     * @var array
     */
    private $uniqueIdentifierFields = [];

    /**
     * @var FieldList
     */
    private $fieldList;

    public function __construct(FieldList $fieldList)
    {
        $this->fieldList = $fieldList;
    }

    /**
     * Retrieves a list of published fields that are unique identifiers.
     *
     * @return mixed
     */
    public function getFieldsWithUniqueIdentifier(array $filters = [])
    {
        $filters['isPublished']       = isset($filters['isPublished']) ? $filters['isPublished'] : true;
        $filters['isUniqueIdentifer'] = isset($filters['isUniqueIdentifer']) ? $filters['isUniqueIdentifer'] : true;
        $filters['object']            = isset($filters['object']) ? $filters['object'] : 'lead';

        $key = base64_encode(json_encode($filters));
        if (!isset($this->uniqueIdentifierFields[$key])) {
            $this->uniqueIdentifierFields[$key] = $this->fieldList->getFieldList(false, true, $filters);
        }

        return $this->uniqueIdentifierFields[$key];
    }
}
