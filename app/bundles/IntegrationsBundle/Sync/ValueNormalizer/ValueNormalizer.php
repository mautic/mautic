<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\ValueNormalizer;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

final class ValueNormalizer implements ValueNormalizerInterface
{
    /**
     * @param mixed $value
     */
    public function normalizeForMautic(string $type, $value): NormalizedValueDAO
    {
        switch ($type) {
            case NormalizedValueDAO::STRING_TYPE:
            case NormalizedValueDAO::TEXT_TYPE:
            case NormalizedValueDAO::TEXTAREA_TYPE:
            case NormalizedValueDAO::URL_TYPE:
            case NormalizedValueDAO::EMAIL_TYPE:
            case NormalizedValueDAO::SELECT_TYPE:
            case NormalizedValueDAO::MULTISELECT_TYPE:
            case NormalizedValueDAO::REGION_TYPE:
            case NormalizedValueDAO::LOOKUP_TYPE:
                return new NormalizedValueDAO($type, $value, (string) $value);
            case NormalizedValueDAO::INT_TYPE:
                return new NormalizedValueDAO($type, $value, (int) $value);
            case NormalizedValueDAO::FLOAT_TYPE:
            case NormalizedValueDAO::DOUBLE_TYPE:
                return new NormalizedValueDAO($type, $value, (float) $value);
            case NormalizedValueDAO::DATE_TYPE:
            case NormalizedValueDAO::DATETIME_TYPE:
                // We expect a string value.
                if (is_string($value)) {
                    return new NormalizedValueDAO($type, $value, new \DateTime($value));
                }

                // Other value types we normalize to null.
                return new NormalizedValueDAO($type, $value, null);
            case NormalizedValueDAO::BOOLEAN_TYPE:
                $value = 'false' === $value ? false : $value;
                $value = 'true' === $value ? true : $value;

                return new NormalizedValueDAO($type, $value, (bool) $value);
            default:
                throw new \InvalidArgumentException('Variable type, '.$type.', not supported');
        }
    }

    /**
     * @return mixed
     */
    public function normalizeForIntegration(NormalizedValueDAO $value)
    {
        return $value->getNormalizedValue();
    }
}
