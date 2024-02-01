<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

class FieldsWithUniqueIdentifier
{
    private array $uniqueIdentifierFields = [];

    public function __construct(
        private FieldList $fieldList
    ) {
    }

    /**
     * Retrieves a list of published fields that are unique identifiers.
     *
     * @return mixed
     */
    public function getFieldsWithUniqueIdentifier(array $filters = [])
    {
        $filters['isPublished'] ??= true;
        $filters['isUniqueIdentifer'] ??= true;
        $filters['object'] ??= 'lead';

        $key = base64_encode(json_encode($filters));
        if (!isset($this->uniqueIdentifierFields[$key])) {
            $this->uniqueIdentifierFields[$key] = $this->fieldList->getFieldList(false, true, $filters);
        }

        return $this->uniqueIdentifierFields[$key];
    }
}
