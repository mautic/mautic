<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field;

use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use Symfony\Component\Yaml\Yaml;

class FieldRepository
{
    /**
     * @return MappedFieldInfo[]
     */
    public function getAllFieldsForMapping(string $objectName): array
    {
        $fieldObjects = $this->getFields($objectName);

        $allFields = [];
        foreach ($fieldObjects as $field) {
            // Fields must have the name as the key
            $allFields[$field->getName()] = new MappedFieldInfo($field);
        }

        return $allFields;
    }

    /**
     * Get and prepare all the fields for the sync.
     *
     * @return Field[]
     */
    public function getFields(string $objectName): array
    {
        // Fetch the fields from field mapping file.
        $fields = [];
        if ($objectName === ConfigSupport::CONTACT) {
            $fields = Yaml::parse(file_get_contents(__DIR__.'/../FieldMappings/ContactFieldMapping.yaml'));
        }
        elseif ($objectName === ConfigSupport::COMPANY) {
            //$fields = Yaml::parse(file_get_contents(__DIR__.'/../FieldMappings/CompanyFieldMapping.yaml'));
            $fields = Yaml::parse(file_get_contents(__DIR__.'/../FieldMappings/ContactFieldMapping.yaml'));
        }

        return $this->hydrateFieldObjects($fields);
    }

    /**
     * @return Field[]
     */
    private function hydrateFieldObjects(array $fields): array
    {
        $fieldObjects = [];
        foreach ($fields as $field) {
            $fieldObjects[$field['name']] = new Field($field);
        }

        return $fieldObjects;
    }
}
