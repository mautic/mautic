<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\DTO\Field;

/**
 * Converts values between HubSpot, which sends and takes every property value as a string, and Mautic.
 */
class ValueNormalizer
{
    public function toMautic(Field $field, mixed $value): NormalizedValueDAO
    {
        $type = $field->getType();

        if (null === $value || '' === $value) {
            // Given as the original value, as NormalizedValueDAO replaces a falsy normalized value with it
            return new NormalizedValueDAO($type, NormalizedValueDAO::STRING_TYPE === $type ? '' : null);
        }

        $value = (string) $value;

        return match ($type) {
            // HubSpot separates the options of a multiple checkbox property with ";", Mautic with "|"
            NormalizedValueDAO::MULTISELECT_TYPE => new NormalizedValueDAO($type, $value, str_replace(';', '|', $value)),
            // NormalizedValueDAO keeps the original value when the normalized one is falsy, so false is given as 0
            NormalizedValueDAO::BOOLEAN_TYPE     => 'true' === $value ? new NormalizedValueDAO($type, $value, true) : new NormalizedValueDAO($type, 0),
            NormalizedValueDAO::FLOAT_TYPE       => new NormalizedValueDAO($type, $value, (float) $value),
            NormalizedValueDAO::DATE_TYPE, NormalizedValueDAO::DATETIME_TYPE => new NormalizedValueDAO($type, $value, $this->toDateTime($value)),
            default => new NormalizedValueDAO($type, $value, $value),
        };
    }

    public function toHubspot(Field $field, NormalizedValueDAO $value): string
    {
        $normalized = $value->getNormalizedValue();

        if (null === $normalized || '' === $normalized) {
            // An empty string clears the property
            return '';
        }

        if ($normalized instanceof \DateTimeInterface) {
            $utc = \DateTimeImmutable::createFromInterface($normalized)->setTimezone(new \DateTimeZone('UTC'));

            return NormalizedValueDAO::DATE_TYPE === $field->getType() ? $utc->format('Y-m-d') : $utc->format('Y-m-d\TH:i:s\Z');
        }

        if (is_bool($normalized)) {
            return $normalized ? 'true' : 'false';
        }

        $string = is_scalar($normalized) ? (string) $normalized : '';

        return match ($field->getType()) {
            NormalizedValueDAO::MULTISELECT_TYPE => str_replace('|', ';', $string),
            NormalizedValueDAO::BOOLEAN_TYPE     => in_array(strtolower($string), ['1', 'true', 'yes'], true) ? 'true' : 'false',
            default                              => $string,
        };
    }

    /**
     * A \DateTime, as the core normalizer gives Mautic.
     */
    private function toDateTime(string $value): ?\DateTime
    {
        try {
            // Older properties may still hold a timestamp in milliseconds
            return ctype_digit($value)
                ? new \DateTime('@'.intdiv((int) $value, 1000))
                : new \DateTime($value, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }
}
