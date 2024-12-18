<?php

namespace Mautic\PluginBundle\Integration;

class IntegrationObject
{
    /**
     * @param string $type
     * @param string $internalType
     */
    public function __construct(
        private $type,
        private $internalType,
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
    public function getInternalType()
    {
        return $this->internalType;
    }
}
