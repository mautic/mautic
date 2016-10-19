<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Event;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class AssetLoadEvent.
 */
class AssetLoadEvent extends CommonEvent
{
    /**
     * @var bool
     */
    protected $unique;

    /**
     * @param Download $download
     */
    public function __construct(Download $download, $isUnique)
    {
        $this->entity = $download;
        $this->unique = $isUnique;
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
