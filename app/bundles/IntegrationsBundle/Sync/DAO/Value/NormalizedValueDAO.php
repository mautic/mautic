<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

class NormalizedValueDAO
{
    public const BOOLEAN_TYPE     = 'boolean';

    public const DATE_TYPE        = 'date';

    public const DATETIME_TYPE    = 'datetime';

    public const DOUBLE_TYPE      = 'double';

    public const EMAIL_TYPE       = 'email';

    public const FLOAT_TYPE       = 'float';

    public const INT_TYPE         = 'int';

    public const LOOKUP_TYPE      = 'lookup';

    public const MULTISELECT_TYPE = 'multiselect';

    public const PHONE_TYPE       = 'phone';

    public const SELECT_TYPE      = 'select';

    public const STRING_TYPE      = 'string';

    public const REGION_TYPE      = 'region';

    public const TEXT_TYPE        = 'text';

    public const TEXTAREA_TYPE    = 'textarea';

    public const TIME_TYPE        = 'time';

    public const URL_TYPE         = 'url';

    public const REFERENCE_TYPE   = 'reference';

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
