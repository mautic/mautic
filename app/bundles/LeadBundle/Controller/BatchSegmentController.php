<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Model\SegmentActionModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class BatchSegmentController extends AbstractFormController
{
    /**
     * @var SegmentActionModel;
     */
    private $actionModel;

    /**
     * @var ListModel;
     */
    private $segmentModel;

    /**
     * Initialize object props here to simulate constructor
     * and make the future controller refactoring easier.
     *
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->actionModel  = $this->container->get('mautic.lead.model.segment.action');
        $this->segmentModel = $this->container->get('mautic.lead.model.list');
    }

    /**
     * API for batch action.
     *
     * @return JsonResponse
     */
    public function setAction()
    {
        $params = $this->request->get('lead_batch', []);
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $segmentsToAdd    = isset($params['add']) ? $params['add'] : [];
            $segmentsToRemove = isset($params['remove']) ? $params['remove'] : [];
            $contactIds       = json_decode($params['ids']);

            $this->actionModel->addContacts($contactIds, $segmentsToAdd);
            $this->actionModel->removeContacts($contactIds, $segmentsToRemove);

            $this->addFlash('mautic.lead.batch_leads_affected', [
                'pluralCount' => count($ids),
                '%count%'     => count($ids),
            ]);
        } else {
            $this->addFlash('mautic.core.error.ids.missing');
        }

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }

    /**
     * View for batch action.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $route = $this->generateUrl('mautic_segment_batch_contact_set');
        $lists = $this->segmentModel->getUserLists();
        $items = [];

        foreach ($lists as $list) {
            $items[$list['id']] = $list['name'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        'lead_batch',
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => 'MauticLeadBundle:Batch:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
