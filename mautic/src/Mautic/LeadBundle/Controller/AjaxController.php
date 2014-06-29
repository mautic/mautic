<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\LeadBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function userListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->get('mautic.factory')->getModel('lead.lead')->getLookupResults('user', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $name        = $r['firstName'] . ' ' . $r['lastName'];
            $dataArray[] = array(
                "label" => $name,
                "value" => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function fieldListAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $filter = InputHelper::clean($request->query->get('filter'));
        $field  = InputHelper::clean($request->query->get('field'));
        if (!empty($field)) {
            $dataArray = array();
            //field_ is attached when looking up in list filters
            if (strpos($field, 'field_') === 0) {
                $field = str_replace('field_', '', $field);
            }
            if ($field == 'company') {
                $results = $this->get('mautic.factory')->getModel('lead.lead')->getLookupResults('company', $filter);
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r['company']);
                }
            } elseif ($field == "owner") {
                $results = $this->get('mautic.factory')->getModel('lead.lead')->getLookupResults('user', $filter);
                foreach ($results as $r) {
                    $name = $r['firstName'] . ' ' . $r['lastName'];
                    $dataArray[] = array(
                        "value" => $name,
                        "id" => $r['id']
                    );
                }
            } else {
                $results = $this->get('mautic.factory')->getModel('lead.field')->getLookupResults($field, $filter);
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r['value']);
                }
            }
        }
        return $this->sendJsonResponse($dataArray);
    }
}