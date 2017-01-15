<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait AjaxLookupControllerTrait
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function getLookupChoiceListAction(Request $request)
    {
        $dataArray = [];
        $modelName = InputHelper::clean($request->query->get('searchKey'));
        $search    = InputHelper::clean($request->query->get(str_replace('.', '_', $modelName)));

        if (!empty($modelName) && !empty($search)) {
            /** @var ModelFactory $modelFactory */
            $modelFactory = $this->get('mautic.model.factory');

            if ($modelFactory->hasModel($modelName)) {
                $model = $modelFactory->getModel($modelName);

                if ($model instanceof AjaxLookupModelInterface) {
                    $results = $model->getLookupResults($modelName, $search);

                    foreach ($results as $result) {
                        if (isset($result['label'])) {
                            $result['text'] = $result['label'];
                        }

                        $dataArray[] = $result;
                    }
                }
            }
        }

        return new JsonResponse($dataArray);
    }
}
