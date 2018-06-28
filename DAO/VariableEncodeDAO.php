<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO;

/**
 * Class VariableEncodeDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO
 */
class VariableEncodeDAO
{
    const STRING_TYPE = 'string';
    const INT_TYPE = 'int';
    const FLOAT_TYPE = 'float';
    const DOUBLE_TYPE = 'double';
    const DATETIME_TYPE = 'datetime';
    const BOOLEAN_TYPE = 'boolean';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;
    
    /**
     * VariableEncodeDAO constructor.
     * @param string $type
     * @param string $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
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
