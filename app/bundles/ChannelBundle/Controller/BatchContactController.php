<?php

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\ChannelActionModel;
use Mautic\ChannelBundle\Model\FrequencyActionModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Form\Type\ContactChannelsType;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class BatchContactController extends AbstractFormController
{
    private ChannelActionModel $channelActionModel;

    private FrequencyActionModel $frequencyActionModel;

    private LeadModel $contactModel;

    #[Required]
    public function autowireBatchContactController(
        ChannelActionModel $channelActionModel,
        FrequencyActionModel $frequencyActionModel,
        LeadModel $contactModel,
    ): void {
        $this->channelActionModel   = $channelActionModel;
        $this->frequencyActionModel = $frequencyActionModel;
        $this->contactModel         = $contactModel;
    }

    /**
     * Execute the batch action.
     */
    #[Route('/s/channels/batch/contact/set', name: 'mautic_channel_batch_contact_set')]
    public function setAction(Request $request): JsonResponse
    {
        $params = $request->get('contact_channels', []);
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $subscribedChannels = $params['subscribed_channels'] ?? [];
            $preferredChannel   = $params['preferred_channel'] ?? null;

            $this->channelActionModel->update($ids, $subscribedChannels);
            $this->frequencyActionModel->update($ids, $params, $preferredChannel);

            $this->addFlashMessage('mautic.lead.batch_leads_affected', [
                '%count%'     => count($ids),
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
    #[Route('/s/channels/batch/contact/view', name: 'mautic_channel_batch_contact_view')]
    public function indexAction(): Response
    {
        $route = $this->generateUrl('mautic_channel_batch_contact_set');

        return $this->delegateView([
            'viewParameters' => [
                'form'         => $this->createForm(ContactChannelsType::class, [], [
                    'action'        => $route,
                    'channels'      => $this->contactModel->getPreferenceChannels(),
                    'public_view'   => false,
                    'save_button'   => true,
                ])->createView(),
            ],
            'contentTemplate' => '@MauticLead/Batch/channel.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'leadBatch',
                'route'         => $route,
            ],
        ]);
    }
}
