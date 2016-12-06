<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // ConfigEvents::CONFIG_POST_SAVE   => array('onConfigPostSave', 0)
        ];
    }
}
