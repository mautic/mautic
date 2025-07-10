<?php

namespace Mautic\AssetBundle\Event;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Event\CommonEvent;

class AssetLoadEvent extends CommonEvent
{
    public function __construct(
        Download $download,
        protected bool $unique,
    ) {
        $this->entity = $download;
    }

    /**
     * Returns the Download entity.
     *
     * @return Download
     */
    public function getRecord()
    {
        return $this->entity;
    }

    /**
     * @return \Mautic\AssetBundle\Entity\Asset
     */
    public function getAsset()
    {
        return $this->entity->getAsset();
    }

    /**
     * Returns if this is the first download for the session.
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }
}
