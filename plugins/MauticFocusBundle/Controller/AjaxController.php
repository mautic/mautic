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
use MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * This method produces HTTP request checking headers which are blocking availability for iframe inheritance for other pages.
     */
    protected function checkIframeAvailabilityAction(Request $request): JsonResponse
    {
        $url = $request->request->get('website');

        /** @var IframeAvailabilityChecker $availabilityChecker */
        $availabilityChecker = $this->get('mautic.focus.helper.iframe_availability_checker');

        return $availabilityChecker->check($url, $request->getScheme());
    }

    protected function generatePreviewAction(Request $request): JsonResponse
    {
        $responseContent  = ['html' => '', 'style' => ''];
        $focus            = $request->request->all();

        if (isset($focus['focus'])) {
            $focusArray = InputHelper::_($focus['focus']);

            if (!empty($focusArray['style']) && !empty($focusArray['type'])) {
                /** @var FocusModel $model */
                $model                    = $this->getModel('focus');
                $focusArray['id']         = 'preview';
                $responseContent['html']  = $model->getContent($focusArray, true);
                $responseContent['style'] = $focusArray['style']; // Required by JS in response
            }
        }

        return $this->sendJsonResponse($responseContent);
    }
}
