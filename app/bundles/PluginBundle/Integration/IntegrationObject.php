<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Integration;

final class IntegrationObject
{
    /**
     * @param string $type
     * @param string $internalType
     */
    public function __construct(
        private string $type,
        private string $internalType,
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
