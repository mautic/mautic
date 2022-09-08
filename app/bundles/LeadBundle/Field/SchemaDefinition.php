<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

class SchemaDefinition
{
    /**
     * Get the MySQL database type based on the field type
     * Use a static function so that it's accessible from DoctrineSubscriber
     * without causing a circular service injection error.
     */
    public static function getSchemaDefinition(string $alias, string $type, bool $isUnique = false): array
    {
        // Unique is always a string in order to control index length
        if ($isUnique) {
            return [
                'name'    => $alias,
                'type'    => 'string',
                'options' => [
                    'notnull' => false,
                ],
            ];
        }
        $schemaLength = null;
        switch ($type) {
            case 'datetime':
            case 'date':
            case 'time':
            case 'boolean':
                $schemaType = $type;
                break;
            case 'number':
                $schemaType = 'float';
                break;
            case 'timezone':
            case 'locale':
            case 'country':
            case 'email':
            case 'lookup':
            case 'select':
            case 'multiselect':
            case 'region':
            case 'tel':
            case 'url':
                $schemaType = 'string';
                break;
            case 'text':
                $schemaType = (false !== strpos($alias, 'description')) ? 'text' : 'string';
                break;
            default:
                $schemaType = 'text';
        }

        if ('string' === $schemaType) {
            $schemaLength = 191;
        }

        return [
            'name'    => $alias,
            'type'    => $schemaType,
            'options' => ['notnull' => false, 'length' => $schemaLength],
        ];
    }

    /**
     * Get the MySQL database type based on the field type.
     */
    public function getSchemaDefinitionNonStatic(string $alias, string $type, bool $isUnique = false): array
    {
        return self::getSchemaDefinition($alias, $type, $isUnique);
    }
}
