<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * Get updated data for context.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getSourceDataAction(Request $request)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->getModel('report');
        $context = $request->get('context');

        $graphs  = $model->getGraphList($context);
        $columns = $model->getColumnList($context);
        $filters = $model->getFilterList($context);

        return $this->sendJsonResponse(
            [
                'columns'           => $columns->choiceHtml,
                'columnDefinitions' => $columns->definitions,
                'filters'           => $filters->choiceHtml,
                'filterDefinitions' => $filters->definitions,
                'filterOperators'   => $filters->operatorHtml,
                'graphs'            => $graphs->choiceHtml,
            ]
        );
    }
}
