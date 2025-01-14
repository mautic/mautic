<?php

namespace Mautic\AssetBundle\Event;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class AssetEvent.
 */
class AssetEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Asset $asset, $isNew = false)
    {
        $this->entity = $asset;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Asset entity.
     *
     * @return Asset
     */
    public function getAsset()
    {
        return $this->entity;
    }

    /**
     * Sets the Asset entity.
     */
    public function setAsset(Asset $asset)
    {
        $this->entity = $asset;
    }
}
