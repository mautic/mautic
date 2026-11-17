<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync\DTO;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

/**
 * A HubSpot property.
 */
final readonly class Field
{
    public function __construct(
        private string $name,
        private string $label,
        private string $type,
        private bool $required,
        private bool $writable,
    ) {
    }

    /**
     * @param array<string, mixed> $property a property definition from the HubSpot properties API
     */
    public static function fromProperty(array $property, bool $required): self
    {
        $type = match ($property['type'] ?? 'string') {
            'bool'        => NormalizedValueDAO::BOOLEAN_TYPE,
            'number'      => NormalizedValueDAO::FLOAT_TYPE,
            'date'        => NormalizedValueDAO::DATE_TYPE,
            'datetime'    => NormalizedValueDAO::DATETIME_TYPE,
            'enumeration' => 'checkbox' === ($property['fieldType'] ?? null) ? NormalizedValueDAO::MULTISELECT_TYPE : NormalizedValueDAO::SELECT_TYPE,
            default       => NormalizedValueDAO::STRING_TYPE,
        };

        // Calculated properties and the ones HubSpot sets itself cannot be written
        $writable = empty($property['modificationMetadata']['readOnlyValue']) && empty($property['calculated']);

        return new self((string) $property['name'], (string) ($property['label'] ?? $property['name']), $type, $required, $writable);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string a NormalizedValueDAO type
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }
}
