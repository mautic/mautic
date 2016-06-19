<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ConfigBundle\Event as Events;
use Mautic\ConfigBundle\ConfigEvents;

/**
 * Class ConfigSubscriber
 *
 * @package Mautic\ConfigBundle\EventListener
 */
class ConfigSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            // ConfigEvents::CONFIG_POST_SAVE   => array('onConfigPostSave', 0)
        );
    }
}
