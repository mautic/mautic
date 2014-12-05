<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

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
     * @param string  $name
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderFieldsAction(Request $request, $name = 'fields')
    {
        $dataArray  = array('success' => 0);
        $sessionId  = InputHelper::clean($request->request->get('formId'));
        $sessionName = 'mautic.form.'.$sessionId.'.' . $name . '.modified';
        $session    = $this->factory->getSession();
        $order      = InputHelper::clean($request->request->get('mauticform_action'));
        $components = $session->get($sessionName);

        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set($sessionName, $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderActionsAction(Request $request) {
        return $this->reorderFieldsAction($request, 'actions');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateSubmissionChartAction(Request $request)
    {
        $formId    = InputHelper::int($request->request->get('formId'));
        $amount    = InputHelper::int($request->request->get('amount'));
        $unit      = InputHelper::clean($request->request->get('unit'));
        $dataArray = array('success' => 0);

        // Download stats per time period
        $dataArray['stats'] = $this->factory->getEntityManager()->getRepository('MauticFormBundle:Submission')->getSubmissionsSince($formId, $amount, $unit);
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
