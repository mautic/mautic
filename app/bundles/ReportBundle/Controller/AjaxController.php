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

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Get updated data for context
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getSourceDataAction(Request $request)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->getModel('report');
        $context = $request->get('context');

        $graphs = $model->getGraphList($context, true);
        list($columns, $columnTypes) = $model->getColumnList($context, true);
        list($filters, $filterTypes, $filterOperators) = $model->getFilterList($context, true);

        return $this->sendJsonResponse(
            [
                'columns'         => $columns,
                'types'           => $columnTypes,
                'filters'         => $filters,
                'filterTypes'     => $filterTypes,
                'filterOperators' => $filterOperators,
                'graphs'          => $graphs,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateGraphAction(Request $request)
    {
        $reportId  = InputHelper::int($request->request->get('reportId'));
        $options   = InputHelper::clean($request->request->all());
        $dataArray = ['success' => 0];

        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->getModel('report');
        $report   = $model->getEntity($reportId);

        $options['ignoreTableData'] = true;
        $reportData                 = $model->getReportData($report, $this->container->get('form.factory'), $options);

        $dataArray['graph']   = $reportData['graphs'][$options['graphName']]['data'];
        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
