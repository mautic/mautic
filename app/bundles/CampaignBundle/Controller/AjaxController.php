<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\CampaignBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderCampaignEventsAction (Request $request)
    {
        $dataArray  = array('success' => 0);
        $session    = $this->factory->getSession();
        $order      = InputHelper::clean($request->request->get('campaignEvent'));
        if (!empty($order)) {
            $session->set('mautic.campaigns.order', $order);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}