<?php

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends CommonApiController<object>
 */
final class StatsApiController extends CommonApiController
{
    /**
     * Lists stats for a database table.
     *
     * @param string $table
     * @param array  $order
     * @param array  $where
     */
    #[Route(
        '/api/stats/{table}',
        name: 'mautic_core_api_stats',
        defaults: ['table' => '', '_format' => 'json'],
        methods: ['GET']
    )]
    public function listAction(Request $request, UserHelper $userHelper, $table = null, string $itemsName = 'stats', $order = [], $where = [], int $start = 0, int $limit = 100): Response
    {
        $response = [];
        $where    = InputHelper::cleanArray(empty($where) ? $request->query->all()['where'] ?? [] : $where);
        $order    = InputHelper::cleanArray(empty($order) ? $request->query->all()['order'] ?? [] : $order);

        $start    = $request->query->getInt('start', $start);
        $limit    = $request->query->getInt('limit', $limit);

        // Ensure internal flag is not spoofed
        $this->sanitizeWhereClauseArrayFromRequest($where);

        try {
            $event = new StatsEvent($table, $start, $limit, $order, $where, $userHelper->getUser());
            $this->dispatcher->dispatch($event);

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
