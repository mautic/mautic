<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

final class NormalizedValueDAO
{
    public const string BOOLEAN_TYPE     = 'boolean';

    public const string DATE_TYPE        = 'date';

    public const string DATETIME_TYPE    = 'datetime';

    public const string DOUBLE_TYPE      = 'double';

    public const string EMAIL_TYPE       = 'email';

    public const string FLOAT_TYPE       = 'float';

    public const string INT_TYPE         = 'int';

    public const string LOOKUP_TYPE      = 'lookup';

    public const string MULTISELECT_TYPE = 'multiselect';

    public const string PHONE_TYPE       = 'phone';

    public const string SELECT_TYPE      = 'select';

    public const string STRING_TYPE      = 'string';

    public const string REGION_TYPE      = 'region';

    public const string TEXT_TYPE        = 'text';

    public const string TEXTAREA_TYPE    = 'textarea';

    public const string TIME_TYPE        = 'time';

    public const string URL_TYPE         = 'url';

    public const string REFERENCE_TYPE   = 'reference';

    /**
     * @var mixed
     */
    private $normalizedValue;

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $normalizedValue
     */
    public function __construct(
        private $type,
        private $value,
        $normalizedValue = null,
    ) {
        $this->normalizedValue = $normalizedValue ?: $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getOriginalValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getNormalizedValue()
    {
        return $this->normalizedValue;
    }
}
