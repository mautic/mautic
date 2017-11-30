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
        $limit     = (int) $request->query->get('limit', 0);
        $start     = (int) $request->query->get('start', 0);
        $options   = $request->query->all();

        if (!empty($modelName) && !empty($search)) {
            /** @var ModelFactory $modelFactory */
            $modelFactory = $this->get('mautic.model.factory');

            if ($modelFactory->hasModel($modelName)) {
                $model = $modelFactory->getModel($modelName);

                if ($model instanceof AjaxLookupModelInterface) {
                    $results = $model->getLookupResults($modelName, $search, $limit, $start, $options);

                    foreach ($results as $group => $result) {
                        $option = [];
                        if (is_array($result)) {
                            if (!isset($result['value'])) {
                                // Grouped options
                                $option = [
                                    'group' => true,
                                    'text'  => $group,
                                    'items' => $result,
                                ];

                                foreach ($result as $value => $label) {
                                    if (is_array($label) && isset($label['label'])) {
                                        $option['items'][$value]['text'] = $label['label'];
                                    }
                                }
                            } else {
                                if (isset($result['label'])) {
                                    $option['text'] = $result['label'];
                                }

                                $option['value'] = $result['value'];
                            }
                        } else {
                            $option[$group] = $result;
                        }

                        if (!empty($option)) {
                            $dataArray[] = $option;
                        }
                    }
                }
            }
        }

        return new JsonResponse($dataArray);
    }
}
