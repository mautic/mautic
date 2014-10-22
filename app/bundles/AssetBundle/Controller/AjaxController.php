<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\AssetBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateDownloadChartAction(Request $request)
    {
        $assetId   = InputHelper::int($request->request->get('assetId'));
        $amount    = InputHelper::int($request->request->get('amount'));
        $unit      = InputHelper::clean($request->request->get('unit'));
        $dataArray = array('success' => 0);

        // Download stats per time period
        $dataArray['stats'] = $this->factory->getEntityManager()->getRepository('MauticAssetBundle:Download')->getDownloads($assetId, $amount, $unit);
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function categoryListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('asset.asset')->getLookupResults('category', $filter, 10);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array(
                "label" => $r['title'] . " ({$r['id']})",
                "value" => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }
}