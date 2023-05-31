<?php

namespace Mautic\LeadBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Form\Type\BatchType;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Model\SegmentActionModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BatchSegmentController extends AbstractFormController
{
    private $actionModel;

    private $segmentModel;

    public function __construct(CorePermissions $security, UserHelper $userHelper, SegmentActionModel $segmentModel, ListModel $listModel, ManagerRegistry $doctrine)
    {
        parent::__construct($security, $userHelper, $doctrine);

        $this->actionModel  = $listModel;
        $this->segmentModel = $segmentModel;
    }

    /**
     * API for batch action.
     *
     * @return JsonResponse
     */
    public function setAction(Request $request)
    {
        $params     = $request->get('lead_batch', []);
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

            $this->addFlashMessage('mautic.lead.batch_leads_affected', [
                '%count%' => count($contactIds),
            ]);
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
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
                'contentTemplate' => '@MauticLead/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
