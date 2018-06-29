<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

/**
 * Class FieldDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report
 */
class FieldChangeDAO
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int|null
     */
    private $changeTimestamp;

    /**
     * FieldDAO constructor.
     * @param string    $name
     * @param mixed     $value
     * @param int       $changeTimestamp
     */
    public function __construct($name, $value, $changeTimestamp)
    {
        $this->name = $name;
        $this->value = $value;
        $this->changeTimestamp = $changeTimestamp;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getChangeTimestamp()
    {
        return $this->changeTimestamp;
    }
}
