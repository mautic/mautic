<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\CampaignBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateConnectionsAction (Request $request)
    {
        $session        = $this->factory->getSession();
        $connections    = $session->get('mautic.campaigns.connections', array());
        $source         = str_replace('CampaignEvent_', '', InputHelper::clean($request->request->get('source')));
        $target         = str_replace('CampaignEvent_', '', InputHelper::clean($request->request->get('target')));
        $sourceEndpoint = InputHelper::clean($request->request->get('sourceEndpoint'));
        $targetEndpoint = InputHelper::clean($request->request->get('targetEndpoint'));
        $remove         = InputHelper::int($request->request->get('remove'));

        if ($remove) {
            unset($connections[$source][$sourceEndpoint]);
        } else {
            $connections[$source][$sourceEndpoint] = $target;
        }

        $session->set('mautic.campaigns.connections', $connections);

        //update the source's canvasSettings
        $events = $session->get('mautic.campaigns.add', array());
        if (isset($events[$source])) {
            $events[$source]['canvasSettings'][$sourceEndpoint]['targetId']       = $target;
            $events[$source]['canvasSettings'][$sourceEndpoint]['targetEndpoint'] = $targetEndpoint;
            $session->set('mautic.campaigns.add', $events);
        }

        return $this->sendJsonResponse(array('connections' => $connections, 'events' => $events));
    }

    protected function updateCoordinatesAction (Request $request)
    {
        $session = $this->factory->getSession();
        $x       = InputHelper::int($request->request->get('droppedX'));
        $y       = InputHelper::int($request->request->get('droppedY'));
        $id      = InputHelper::int($request->request->get('eventId'));

        //update the source's canvasSettings
        $events = $session->get('mautic.campaigns.add', array());
        if (isset($events[$id])) {
            $events[$id]['canvasSettings']['droppedX'] = $x;
            $events[$id]['canvasSettings']['droppedY'] = $y;
            $session->set('mautic.campaigns.add', $events);
        }

        return $this->sendJsonResponse(array('events' => $events));
    }
}