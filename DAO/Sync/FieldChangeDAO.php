<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class FieldChangeDAO.
 */
class FieldChangeDAO
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int|null
     */
    private $certainChangeTimestamp = null;

    /**
     * @var int|null
     */
    private $possibleChangeTimestamp = null;

    /**
     * FieldChangeDAO constructor.
     * @param string $field
     * @param mixed  $value
     */
    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getCertainChangeTimestamp()
    {
        return $this->certainChangeTimestamp;
    }

    /**
     * @param int|null $certainChangeTimestamp
     * @return FieldChangeDAO
     */
    public function setCertainChangeTimestamp($certainChangeTimestamp)
    {
        $this->certainChangeTimestamp = $certainChangeTimestamp;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPossibleChangeTimestamp()
    {
        return $this->possibleChangeTimestamp;
    }

    /**
     * @param int|null $possibleChangeTimestamp
     * @return FieldChangeDAO
     */
    public function setPossibleChangeTimestamp($possibleChangeTimestamp)
    {
        $this->possibleChangeTimestamp = $possibleChangeTimestamp;
        return $this;
    }
}
