<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

class NormalizedValueDAO
{
    const BOOLEAN_TYPE     = 'boolean';
    const DATE_TYPE        = 'date';
    const DATETIME_TYPE    = 'datetime';
    const DOUBLE_TYPE      = 'double';
    const EMAIL_TYPE       = 'email';
    const FLOAT_TYPE       = 'float';
    const INT_TYPE         = 'int';
    const LOOKUP_TYPE      = 'lookup';
    const MULTISELECT_TYPE = 'multiselect';
    const PHONE_TYPE       = 'phone';
    const SELECT_TYPE      = 'select';
    const STRING_TYPE      = 'string';
    const REGION_TYPE      = 'region';
    const TEXT_TYPE        = 'text';
    const TEXTAREA_TYPE    = 'textarea';
    const TIME_TYPE        = 'time';
    const URL_TYPE         = 'url';
    const REFERENCE_TYPE   = 'reference';

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var mixed
     */
    private $normalizedValue;

    /**
     * @param string $type
     * @param mixed  $value
     * @param mixed  $normalizedValue
     */
    public function __construct($type, $value, $normalizedValue = null)
    {
        $this->type            = $type;
        $this->value           = $value;
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
