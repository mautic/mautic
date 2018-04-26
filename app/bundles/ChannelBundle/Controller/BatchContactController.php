<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\ChannelActionModel;
use Mautic\ChannelBundle\Model\FrequencyActionModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\LeadBundle\Form\Type\ContactChannelsType;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class BatchContactController extends AbstractFormController
{
    /**
     * @var ChannelActionModel
     */
    private $channelActionModel;

    /**
     * @var FrequencyActionModel
     */
    private $frequencyActionModel;

    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * Initialize object props here to simulate constructor
     * and make the future controller refactoring easier.
     *
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->channelActionModel   = $this->container->get('mautic.channel.model.channel.action');
        $this->frequencyActionModel = $this->container->get('mautic.channel.model.frequency.action');
        $this->contactModel         = $this->container->get('mautic.lead.model.lead');
    }

    /**
     * Execute the batch action.
     *
     * @return JsonResponse
     */
    public function setAction()
    {
        $params = $this->request->get('contact_channels', []);
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $subscribedChannels = isset($params['subscribed_channels']) ? $params['subscribed_channels'] : [];
            $preferredChannel   = isset($params['preferred_channel']) ? $params['preferred_channel'] : null;

            $this->channelActionModel->update($ids, $subscribedChannels);
            $this->frequencyActionModel->update($ids, $params, $preferredChannel);

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
            'contentTemplate' => 'MauticLeadBundle:Batch:channel.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'leadBatch',
                'route'         => $route,
            ],
        ]);
    }
}
