<?php

namespace Mautic\CategoryBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    private \Mautic\CategoryBundle\Model\CategoryModel $categoryModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\CategoryBundle\Model\CategoryModel $categoryModel): void
    {
        $this->categoryModel = $categoryModel;
    }

    public function categoryListAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $bundle        = InputHelper::clean($request->query->get('bundle'));
        $filter        = InputHelper::clean($request->query->get('filter'));
        $results   = $this->categoryModel->getLookupResults($bundle, $filter, 10);
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
