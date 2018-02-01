<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildRobotsTxtEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RobotsTxtController.
 */
class RobotsTxtController extends CommonController
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $dispatcher = $this->dispatcher;
        $debug      = $this->factory->getKernel()->isDebug();
        $event      = new BuildRobotsTxtEvent($this->getDefaultContent(), $debug);

        if ($dispatcher->hasListeners(CoreEvents::BUILD_MAUTIC_ROBOTS_TXT)) {
            $dispatcher->dispatch(CoreEvents::BUILD_MAUTIC_ROBOTS_TXT, $event);
        }

        return new Response($event->getContent());
    }

    /**
     * Build a Default Content for the Mautic embedded Robots.txt.
     *
     * @return string
     */
    protected function getDefaultContent()
    {
        return 'User-agent: *
Disallow: /addons/
Disallow: /plugins/
Disallow: /app/
Disallow: /media/dashboards/
Disallow: /media/files/
Disallow: /media/js/mautic-form-src.js
Disallow: /themes/
Disallow: /translations/
Disallow: /vendor/
';
    }
}
