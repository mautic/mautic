<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\DAO\Value;

/**
 * Class NormalizedValueDAO
 */
class NormalizedValueDAO
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
     * @var mixed
     */
    private $value;

    /**
     * @var mixed
     */
    private $normalizedValue;

    /**
     * NormalizedValueDAO constructor.
     *
     * @param string $type
     * @param mixed  $value
     * @param mixed  $normalizedValue
     */
    public function __construct($type, $value, $normalizedValue)
    {
        $this->type            = $type;
        $this->value           = $value;
        $this->normalizedValue = $normalizedValue;
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
