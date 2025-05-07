<?php

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends CommonApiController<object>
 */
class StatsApiController extends CommonApiController
{
    /**
     * Lists stats for a database table.
     *
     * @param string $table
     * @param string $itemsName
     * @param array  $order
     * @param array  $where
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, UserHelper $userHelper, $table = null, $itemsName = 'stats', $order = [], $where = [], $start = 0, $limit = 100)
    {
        $response = [];
        $where    = InputHelper::cleanArray(empty($where) ? $request->query->all()['where'] ?? [] : $where);
        $order    = InputHelper::cleanArray(empty($order) ? $request->query->all()['order'] ?? [] : $order);
        $start    = (int) $request->query->get('start', $start);
        $limit    = (int) $request->query->get('limit', $limit);

        // Ensure internal flag is not spoofed
        $this->sanitizeWhereClauseArrayFromRequest($where);

        try {
            $event = new StatsEvent($table, $start, $limit, $order, $where, $userHelper->getUser());
            $this->dispatcher->dispatch($event, CoreEvents::LIST_STATS);

            // Return available tables if no result was set
            if (!$event->hasResults()) {
                $response['availableTables'] = $event->getTables();
                $response['tableColumns']    = $event->getTableColumns();
            } else {
                $results              = $event->getResults();
                $response['total']    = $results['total'];
                $response[$itemsName] = $results['results'];
            }
        } catch (\Exception $e) {
            $response['errors'] = [
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ],
            ];
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }
}
