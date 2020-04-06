<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function generatePreviewAction(Request $request)
    {
        $data  = ['html' => '', 'style' => ''];
        $focus = $request->request->all();

        if (isset($focus['focus'])) {
            $focusArray = InputHelper::_($focus['focus']);

            if (!empty($focusArray['style']) && !empty($focusArray['type'])) {
                /** @var \MauticPlugin\MauticFocusBundle\Model\FocusModel $model */
                $model            = $this->getModel('focus');
                $focusArray['id'] = 'preview';
                $data['html']     = $model->getContent($focusArray, true);
            }
        }

        return $this->sendJsonResponse($data);
    }
}
