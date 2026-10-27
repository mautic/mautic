<?php

namespace Mautic\CategoryBundle\Controller;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private CategoryModel $categoryModel;

    #[Required]
    public function autowireCategoryAjaxController(
        CategoryModel $categoryModel,
    ): void {
        $this->categoryModel = $categoryModel;
    }

    public function categoryListAction(Request $request): JsonResponse
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
