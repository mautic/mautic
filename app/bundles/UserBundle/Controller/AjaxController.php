<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\FormBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @return \Mautic\CoreBundle\Controller\JsonResponse
     */
    protected function roleListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->get('mautic.factory')->getModel('user.user')->getLookupResults('role', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array(
                'label' => $r['name'],
                'value' => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return \Mautic\CoreBundle\Controller\JsonResponse
     */
    protected function positionListAction(Request $request)
    {
        $filter  = InputHelper::clean($request->query->get('filter'));
        $results = $this->get('mautic.factory')->getModel('user.user')->getLookupResults('position', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array('value' => $r['position']);
        }

        return $this->sendJsonResponse($dataArray);
    }
}