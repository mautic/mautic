<?php

namespace Mautic\CategoryBundle\Controller;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function categoryListAction(Request $request)
    {
        $bundle        = InputHelper::clean($request->query->get('bundle'));
        $filter        = InputHelper::clean($request->query->get('filter'));
        $categoryModel = $this->getModel('category');
        \assert($categoryModel instanceof CategoryModel);
        $results   = $categoryModel->getLookupResults($bundle, $filter, 10);
        $dataArray = [];
        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']})",
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }
}
