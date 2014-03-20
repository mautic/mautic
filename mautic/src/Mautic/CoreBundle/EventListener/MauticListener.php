<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\EventsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class MauticListener
 * Mautic's Event Listener Functions
 *
 * @package Mautic\CoreBundle\EventListener
 */
class MauticListener
{

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        //only affect Mautic controllers
        if ($controller[0] instanceof EventsController) {
            //populate request attributes with  namespace, bundle, controller, and action names for use in bundle controllers and templates
            $request    = $event->getRequest();
            $matches    = array();
            $controller = $request->attributes->get('_controller');
            preg_match('/(.*)\\\(.*)Bundle\\\Controller\\\(.*)Controller::(.*)Action/', $controller, $matches);

            if (!empty($matches)) {
                $request->attributes->set('namespace', $matches[1]);
                $request->attributes->set('bundle', $matches[2]);
                $request->attributes->set('controller', $matches[3]);
                $request->attributes->set('action', $matches[4]);
            } else {
                preg_match('/Mautic(.*)Bundle:(.*):(.*)/', $controller, $matches);
                if (!empty($matches)) {
                    $request->attributes->set('namespace', 'Mautic');
                    $request->attributes->set('bundle', $matches[1]);
                    $request->attributes->set('controller', $matches[2]);
                    $request->attributes->set('action', $matches[3]);
                }
            }
        }
    }
}