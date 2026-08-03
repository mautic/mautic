<?php

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends CommonApiController<object>
 */
final class StatsApiController extends CommonApiController
{
    public function __construct(
        \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        \Mautic\CoreBundle\Translation\Translator $translator,
        \Mautic\ApiBundle\Helper\EntityResultHelper $entityResultHelper,
        protected \Symfony\Component\Routing\RouterInterface $router,
        protected \Symfony\Component\Form\FormFactoryInterface $formFactory,
        \Mautic\CoreBundle\Helper\AppVersion $appVersion,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        \Doctrine\Persistence\ManagerRegistry $doctrine,
        \Mautic\CoreBundle\Factory\ModelFactory $modelFactory,
        \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        private readonly UserHelper $userHelper,
    ) {
        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Lists stats for a database table.
     *
     * @param string $table
     * @param array  $order
     * @param array  $where
     */
    public function listAction(Request $request, $table = null, string $itemsName = 'stats', $order = [], $where = [], int $start = 0, int $limit = 100): Response
    {
        $response = [];
        $where    = InputHelper::cleanArray(empty($where) ? $request->query->all()['where'] ?? [] : $where);
        $order    = InputHelper::cleanArray(empty($order) ? $request->query->all()['order'] ?? [] : $order);

        $start    = $request->query->getInt('start', $start);
        $limit    = $request->query->getInt('limit', $limit);

        // Ensure internal flag is not spoofed
        $this->sanitizeWhereClauseArrayFromRequest($where);

        try {
            $event = new StatsEvent($table, $start, $limit, $order, $where, $this->userHelper->getUser());
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
