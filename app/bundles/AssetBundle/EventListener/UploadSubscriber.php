<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Event\PreUploadEvent;
use Oneup\UploaderBundle\UploadEvents;

/**
 * Class UploadSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class UploadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            UploadEvents::PRE_UPLOAD => array('preUpload', 0)
        );
    }

    /**
     * ...
     *
     * @param Events\AssetEvent $event
     */
    public function preUpload(PreUploadEvent $event)
    {
        // TODO
        // echo "<pre>";\Doctrine\Common\Util\Debug::dump($event);die("</pre>");
    }
}
