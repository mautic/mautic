<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    private ReportModel $reportModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(ReportModel $reportModel): void
    {
        $this->reportModel = $reportModel;
    }

    /**
     * Get updated data for context.
     */
    public function getSourceDataAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $context = $request->get('context');

        $graphs  = $this->reportModel->getGraphList($context);
        $columns = $this->reportModel->getColumnList($context);
        $filters = $this->reportModel->getFilterList($context);

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
