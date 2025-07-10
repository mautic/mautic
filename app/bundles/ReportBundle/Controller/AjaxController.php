<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * Get updated data for context.
     */
    public function getSourceDataAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $model = $this->getModel('report');
        \assert($model instanceof ReportModel);
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
