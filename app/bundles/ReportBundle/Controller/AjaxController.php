<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Update the column lists
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateColumnsAction(Request $request)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model  = $this->factory->getModel('report');
        $tables = $model->getTableData();

        $dataArray = array(
            'columns' => $tables[$request->get('table')]['columns']
        );

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateGraphAction(Request $request)
    {
        $reportId   = InputHelper::int($request->request->get('reportId'));
        $options   = InputHelper::clean($request->request->all());
        $dataArray = array('success' => 0);

        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->factory->getModel('report');
        $report   = $model->getEntity($reportId);

        $event = new ReportGraphEvent($report);
        $event->setOptions($options);
        $this->factory->getDispatcher()->dispatch(ReportEvents::REPORT_ON_GRAPH_GENERATE, $event);
        $dataArray['graph'] = $event->getGraphs();
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
