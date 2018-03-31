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
use Symfony\Component\HttpFoundation\JsonResponse;

class BatchLeadSegmentsController extends AbstractFormController
{
    /**
     * API for batch action.
     *
     * @return JsonResponse
     */
    public function batchApiAction()
    {
        $actionFactory     = $this->get('mautic.lead.batch.change_segments_action_factory');
        $requestParameters = $this->request->get('lead_batch', []);

        if (array_key_exists('ids', $requestParameters)) {
            $segmentsToAdd    = array_key_exists('add', $requestParameters) ? $requestParameters['add'] : [];
            $segmentsToRemove = array_key_exists('remove', $requestParameters) ? $requestParameters['remove'] : [];

            $action = $actionFactory->create(json_decode($requestParameters['ids']), $segmentsToAdd, $segmentsToRemove);
            $action->execute();

            $this->addFlash('mautic.lead.batch_leads_affected', [
                'pluralCount' => count($requestParameters['ids']),
                '%count%'     => count($requestParameters['ids']),
            ]);

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }
    }

    /**
     * View for batch action.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function batchViewAction()
    {
        $listModel = $this->get('mautic.lead.model.list');
        $route     = $this->generateUrl('mautic_contact_batch_segments_api');

        $lists = $listModel->getUserLists();
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
