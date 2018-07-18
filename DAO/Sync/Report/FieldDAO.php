<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

/**
 * Class FieldDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report
 */
class FieldDAO
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
    private $changeTimestamp = null;

    /**
     * FieldDAO constructor.
     * @param string    $name
     * @param mixed     $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
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
     * @return int|null
     */
    public function getChangeTimestamp(): ?int
    {
        return $this->changeTimestamp;
    }

    /**
     * @param int|null $changeTimestamp
     *
     * @return FieldDAO
     */
    public function setChangeTimestamp(?int $changeTimestamp): FieldDAO
    {
        $this->changeTimestamp = $changeTimestamp;

        return $this;
    }
}
