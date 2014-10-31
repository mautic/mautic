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
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Mautic\CoreBundle\Controller\JsonResponse
     */
    protected function roleListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('user')->getLookupResults('role', $filter);
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
     * @param Request $request
     *
     * @return \Mautic\CoreBundle\Controller\JsonResponse
     */
    protected function userListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('user')->getLookupResults('user', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array(
                'label' => $r['firstName'] . ' ' . $r['lastName'],
                'value' => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Mautic\CoreBundle\Controller\JsonResponse
     */
    protected function positionListAction(Request $request)
    {
        $filter  = InputHelper::clean($request->query->get('filter'));
        $results = $this->factory->getModel('user')->getLookupResults('position', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array('value' => $r['position']);
        }

        return $this->sendJsonResponse($dataArray);
    }
}
