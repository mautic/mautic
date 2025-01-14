<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

class EncodedValueDAO
{
    const STRING_TYPE   = 'string';
    const INT_TYPE      = 'int';
    const FLOAT_TYPE    = 'float';
    const DOUBLE_TYPE   = 'double';
    const DATETIME_TYPE = 'datetime';
    const BOOLEAN_TYPE  = 'boolean';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $type
     * @param string $value
     */
    public function __construct($type, $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
