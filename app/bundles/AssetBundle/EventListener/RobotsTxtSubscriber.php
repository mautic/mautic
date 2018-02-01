<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildRobotsTxtEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class RobotsTxtSubscriber.
 */
class RobotsTxtSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::BUILD_MAUTIC_ROBOTS_TXT   => ['onRobotsTxtBuild', 0],
        ];
    }

    /**
     * @param BuildRobotsTxtEvent $event
     */
    public function onRobotsTxtBuild(BuildRobotsTxtEvent $event)
    {
        $content = '';
        $event->appendContent($content);
    }
}
