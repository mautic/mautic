<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class StatsApiController.
 */
class StatsApiController extends CommonApiController
{
    /**
     * Lists stats for a database table.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction($table = null)
    {
        $response                   = [];
        $where                      = InputHelper::clean($this->request->query->get('where', []));
        $order                      = InputHelper::clean($this->request->query->get('order', []));
        $start                      = (int) $this->request->query->get('start', 0);
        $limit                      = (int) $this->request->query->get('limit', 100);
        $filter['hydration_mode']   = 'HYDRATE_ARRAY';
        $filter['ignore_paginator'] = true;

        $event = new StatsEvent($table, $start, $limit, $order, $where);
        $this->get('event_dispatcher')->dispatch(CoreEvents::LIST_STATS, $event);

        // if ($event->getResults() === null) {
        //     return $this->notFound();
        // }
        $response['stats'] = $event->getResults();

        $view = $this->view($response);

        return $this->handleView($view);
    }
}
