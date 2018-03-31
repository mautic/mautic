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

class BatchLeadChannelsController extends AbstractFormController
{
    /**
     * API for batch action.
     *
     * @return JsonResponse
     */
    public function batchApiAction()
    {
        $actionFactory     = $this->get('mautic.lead.batch.change_channels_action_factory');
        $requestParameters = $this->request->get('lead_contact_channels', []);

        if (array_key_exists('ids', $requestParameters)) {
            $subscribedChannels = array_key_exists('subscribed_channels', $requestParameters) ? $requestParameters['subscribed_channels'] : [];
            $preferredChannel   = array_key_exists('preferred_channel', $requestParameters) ? $requestParameters['preferred_channel'] : null;

            $action = $actionFactory->create(
                json_decode($requestParameters['ids']),
                $subscribedChannels,
                $requestParameters,
                $preferredChannel
            );

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
        $leadModel = $this->get('mautic.lead.model.lead');
        $route     = $this->generateUrl('mautic_contact_batch_channels_api');

        return $this->delegateView([
            'viewParameters' => [
                'form'         => $this->createForm('lead_contact_channels', [], [
                    'action'        => $route,
                    'channels'      => $leadModel->getPreferenceChannels(),
                    'public_view'   => false,
                    'save_button'   => true,
                ])->createView(),
            ],
            'contentTemplate' => 'MauticLeadBundle:Batch:channel.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'leadBatch',
                'route'         => $route,
            ],
        ]);
    }
}
