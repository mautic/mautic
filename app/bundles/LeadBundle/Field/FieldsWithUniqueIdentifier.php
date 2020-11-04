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
     * Retrieves a list of cached published fields that are unique identifiers.
     *
     * @return mixed
     */
    public function getFieldsWithUniqueIdentifier(array $filters = [])
    {
        $filters = $this->prepareFilters($filters);

        $key = base64_encode(json_encode($filters));
        if (!isset($this->uniqueIdentifierFields[$key])) {
            $this->uniqueIdentifierFields[$key] = $this->fieldList->getFieldList(false, true, $filters);
        }

        return $this->uniqueIdentifierFields[$key];
    }

    /**
     * Retrieves a list of published fields that are unique identifiers fresh from the DB each time.
     *
     * @param array $filters
     */
    public function getLiveFields(array $filters = []): array
    {
        $filters = $this->prepareFilters($filters);

        return $this->fieldList->getFieldList(false, true, $filters);
    }

    private function prepareFilters(array $filters): array
    {
        $filters['isPublished'] ??= true;
        $filters['isUniqueIdentifer'] ??= true;
        $filters['object'] ??= 'lead';

        return $filters;
    }
}
