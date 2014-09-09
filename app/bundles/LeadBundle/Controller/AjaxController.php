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
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;
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
        $results   = $this->factory->getModel('lead.lead')->getLookupResults('user', $filter);
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
            if ($field == 'company') {
                $results = $this->factory->getModel('lead.lead')->getLookupResults('company', $filter);
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r['company']);
                }
            } elseif ($field == "owner") {
                $results = $this->factory->getModel('lead.lead')->getLookupResults('user', $filter);
                foreach ($results as $r) {
                    $name = $r['firstName'] . ' ' . $r['lastName'];
                    $dataArray[] = array(
                        "value" => $name,
                        "id" => $r['id']
                    );
                }
            } else {
                $results = $this->factory->getModel('lead.field')->getLookupResults($field, $filter);
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r[$field]);
                }
            }
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Updates the cache and gets returns updated HTML
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateSocialProfileAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $network = InputHelper::clean($request->request->get('network'));
        $leadId  = InputHelper::clean($request->request->get('lead'));

        if (!empty($leadId)) {
            //find the lead
            $model = $this->factory->getModel('lead.lead');
            $lead = $model->getEntity($leadId);

            if ($lead !== null) {
                $fields            = $lead->getFields();
                $socialProfiles    = NetworkIntegrationHelper::getUserProfiles($this->factory, $lead, $fields, true, $network);
                $socialProfileUrls = NetworkIntegrationHelper::getSocialProfileUrlRegex(false);
                $networks = array();
                foreach ($socialProfiles as $name => $details) {
                    $networks[$name]['newContent'] = $this->renderView('MauticLeadBundle:Social/' . $name . ':view.html.php', array(
                        'lead'              => $lead,
                        'details'           => $details,
                        'network'           => $name,
                        'socialProfileUrls' => $socialProfileUrls
                    ));
                }

                $dataArray['success']  = 1;
                $dataArray['profiles'] = $networks;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function toggleLeadListAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $leadId    = InputHelper::int($request->request->get('leadId'));
        $listId    = InputHelper::int($request->request->get('listId'));
        $action    = InputHelper::clean($request->request->get('listAction'));

        if (!empty($leadId) && !empty($listId) && in_array($action, array('remove', 'add'))) {
            $leadModel = $this->factory->getModel('lead');
            $listModel = $this->factory->getModel('lead.list');

            $lead = $leadModel->getEntity($leadId);
            $list = $listModel->getEntity($listId);

            if ($lead !== null && $list !== null) {
                $class = "{$action}Lead";
                $listModel->$class($lead, $list);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

}