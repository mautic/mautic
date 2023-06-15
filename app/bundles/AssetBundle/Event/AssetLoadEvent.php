<?php

namespace Mautic\AssetBundle\Event;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class AssetLoadEvent.
 */
class AssetLoadEvent extends CommonEvent
{
    /**
     * @param bool $isUnique
     */
    public function __construct(Download $download, protected $unique)
    {
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
     *
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }
}
