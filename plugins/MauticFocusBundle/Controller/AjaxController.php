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
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getWebsiteSnapshotAction(Request $request)
    {
        $data = ['success' => 0];

        if ($this->get('mautic.security')->isGranted('plugin:focus:items:create')) {
            $website = InputHelper::url($request->request->get('website'));

            if ($website) {
                // Let's try to extract colors from image
                $id = InputHelper::int($request->request->get('id'));
                if (!empty($id)) {
                    // Tell the JS to not populate with default colors
                    $data['ignoreDefaultColors'] = true;
                }

                $snapshotUrl = $this->get('mautic.helper.core_parameters')->getParameter('website_snapshot_url');
                $snapshotKey = $this->get('mautic.helper.core_parameters')->getParameter('website_snapshot_key');

                $http     = $this->get('mautic.http.connector');
                $response = $http->get($snapshotUrl.'?url='.urlencode($website).'&key='.$snapshotKey, [], 30);

                if ($response->code === 200) {
                    $package = json_decode($response->body, true);
                    if (isset($package['images'])) {
                        $data['image']['desktop'] = $package['images']['desktop'];
                        $data['image']['mobile']  = $package['images']['mobile'];
                        $palette                  = $package['palette'];
                        $data['colors']           = [
                            'primaryColor'    => $palette[0],
                            'textColor'       => FocusModel::isLightColor($palette[0]) ? '#000000' : '#ffffff',
                            'buttonColor'     => $palette[1],
                            'buttonTextColor' => FocusModel::isLightColor($palette[1]) ? '#000000' : '#ffffff',
                        ];
                        $data['success'] = 1;
                    }
                }
            }
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
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
