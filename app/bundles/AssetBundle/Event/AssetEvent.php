<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\AssetBundle\Entity\Asset;

/**
 * Class AssetEvent
 *
 * @package Mautic\AssetBundle\Event
 */
class AssetEvent extends CommonEvent
{
    /**
     * @param Form $asset
     * @param bool $isNew
     */
    public function __construct(Asset &$asset, $isNew = false)
    {
        $this->entity  =& $asset;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Asset entity
     *
     * @return Asset
     */
    public function getAsset()
    {
        return $this->entity;
    }

    /**
     * Sets the Asset entity
     *
     * @param Asset $asset
     */
    public function setAsset(Asset $asset)
    {
        $this->entity = $asset;
    }
}