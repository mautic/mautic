<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Form\Type\BatchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class BatchSegmentController extends AbstractFormController
{
    private $actionModel;

    private $segmentModel;

    /**
     * Initialize object props here to simulate constructor
     * and make the future controller refactoring easier.
     */
    public function initialize(ControllerEvent $event)
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
        $params     = $this->request->get('lead_batch', []);
        $contactIds = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($contactIds && is_array($contactIds)) {
            $segmentsToAdd    = $params['add'] ?? [];
            $segmentsToRemove = $params['remove'] ?? [];

            if ($segmentsToAdd) {
                $this->actionModel->addContacts($contactIds, $segmentsToAdd);
            }

            if ($segmentsToRemove) {
                $this->actionModel->removeContacts($contactIds, $segmentsToRemove);
            }

            $this->addFlash('mautic.lead.batch_leads_affected', [
                '%count%' => count($contactIds),
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
            $items[$list['name']] = $list['id'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        BatchType::class,
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => 'MauticLeadBundle:Batch:form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
