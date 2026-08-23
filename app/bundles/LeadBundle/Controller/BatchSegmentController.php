<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Form\Type\BatchType;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Model\SegmentActionModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class BatchSegmentController extends AbstractFormController
{
    private SegmentActionModel $segmentActionModel;

    private ListModel $segmentModel;

    #[Required]
    public function autowireBatchSegmentController(
        SegmentActionModel $segmentActionModel,
        ListModel $segmentModel,
    ): void {
        $this->segmentActionModel = $segmentActionModel;
        $this->segmentModel       = $segmentModel;
    }

    /**
     * API for batch action.
     */
    #[Route(
        '/s/segments/batch/contact/set',
        name: 'mautic_segment_batch_contact_set',
        priority: -682
    )]
    public function setAction(Request $request): JsonResponse
    {
        $params     = $request->query->all()['lead_batch'] ?? $request->request->all()['lead_batch'] ?? [];
        $contactIds = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($contactIds && is_array($contactIds)) {
            $segmentsToAdd    = $params['add'] ?? [];
            $segmentsToRemove = $params['remove'] ?? [];

            if ($segmentsToAdd) {
                $this->segmentActionModel->addContacts($contactIds, $segmentsToAdd);
            }

            if ($segmentsToRemove) {
                $this->segmentActionModel->removeContacts($contactIds, $segmentsToRemove);
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
     */
    #[Route(
        '/s/segments/batch/contact/view',
        name: 'mautic_segment_batch_contact_view',
        priority: -683
    )]
    public function indexAction(): Response
    {
        $route = $this->generateUrl('mautic_segment_batch_contact_set');
        $lists = $this->segmentModel->getUserLists();
        $items = [];

        foreach ($lists as $list) {
            $items[$list['name'].' ('.$list['id'].')'] = $list['id'];
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
