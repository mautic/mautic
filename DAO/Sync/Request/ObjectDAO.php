<?php
declare(strict_types=1);

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request;

/**
 * Class ObjectDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request
 */
class ObjectDAO
{
    /**
     * @var string
     */
    private $object;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * ObjectDAO constructor.
     *
     * @param $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @param string $field
     *
     * @return self
     */
    public function addField(string $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
