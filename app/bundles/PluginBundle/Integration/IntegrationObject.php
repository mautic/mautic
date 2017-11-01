<?php

namespace Mautic\PluginBundle\Integration;

class IntegrationObject
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $internalType;

    /**
     * Constructor.
     */
    public function __construct($type, $internalType)
    {
        $this->type         = $type;
        $this->internalType = $internalType;
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
    public function getInternalType()
    {
        return $this->internalType;
    }
}
