<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Returns form HTML. Used for AJAX calls which modifies form field elements.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getFormAction(Request $request)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->factory->getModel('report');
        $entity  = $model->getEntity();
        $action  = $this->generateUrl('mautic_report_action', array('objectAction' => 'new'));
        $form    = $model->createForm($entity, $this->get('form.factory'), $action);
        $form->handleRequest($request);

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'report'      => $entity,
                'form'        => $this->setFormTheme($form, 'MauticReportBundle:Report:form.html.php', 'MauticReportBundle:FormTheme\Report'),
            ),
            'contentTemplate' => 'MauticReportBundle:Report:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
                'route'         => $this->generateUrl('mautic_report_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateGraphAction(Request $request)
    {
        $reportId   = InputHelper::int($request->request->get('reportId'));
        $options   = InputHelper::clean($request->request->all());
        $dataArray = array('success' => 0);

        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->factory->getModel('report');
        $report   = $model->getEntity($reportId);

        $event = new ReportGraphEvent($report);
        $event->setOptions($options);
        $this->factory->getDispatcher()->dispatch(ReportEvents::REPORT_ON_GRAPH_GENERATE, $event);
        $dataArray['graph'] = $event->getGraphs();
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
