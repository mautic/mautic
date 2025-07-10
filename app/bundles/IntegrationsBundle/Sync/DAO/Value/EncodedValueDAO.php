<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

class EncodedValueDAO
{
    public const STRING_TYPE   = 'string';

    public const INT_TYPE      = 'int';

    public const FLOAT_TYPE    = 'float';

    public const DOUBLE_TYPE   = 'double';

    public const DATETIME_TYPE = 'datetime';

    public const BOOLEAN_TYPE  = 'boolean';

    /**
     * @param string $type
     * @param string $value
     */
    public function __construct(
        private $type,
        private $value,
    ) {
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
