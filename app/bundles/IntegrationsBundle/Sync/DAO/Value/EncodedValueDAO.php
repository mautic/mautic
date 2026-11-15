<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Value;

final readonly class EncodedValueDAO
{
    public const string STRING_TYPE   = 'string';

    public const string INT_TYPE      = 'int';

    public const string FLOAT_TYPE    = 'float';

    public const string DOUBLE_TYPE   = self::FLOAT_TYPE; // float and double are the same in PHP

    public const string DATETIME_TYPE = 'datetime';

    public const string BOOLEAN_TYPE  = 'boolean';

    public function __construct(
        private string $type,
        private string $value,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
