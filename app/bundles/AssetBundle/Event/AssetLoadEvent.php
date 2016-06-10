<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Event;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class AssetLoadEvent
 *
 * @package Mautic\AssetBundle\Event
 */
class AssetLoadEvent extends CommonEvent
{
    /**
     * @param Download $download
     */
    public function __construct(Download $download)
    {
        $this->entity = $download;
    }

    /**
     * Returns the Download entity
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
}
