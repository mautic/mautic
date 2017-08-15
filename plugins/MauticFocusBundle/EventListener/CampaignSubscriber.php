<?php

/*
 * @copyright   2017 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Helper\TrackingHelper;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var FocusModel
     */
    protected $focusModel;

    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * CampaignSubscriber constructor.
     *
     * @param EventModel      $eventModel
     * @param FocusModel      $focusModel
     * @param TrackingHelper  $trackingHelper
     * @param RouterInterface $router
     */
    public function __construct(EventModel $eventModel, FocusModel $focusModel, TrackingHelper $trackingHelper, RouterInterface $router)
    {
        $this->campaignEventModel = $eventModel;
        $this->focusModel         = $focusModel;
        $this->trackingHelper     = $trackingHelper;
        $this->router             = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            FocusEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'                  => 'mautic.focus.campaign.event.show_focus',
            'description'            => 'mautic.focus.campaign.event.show_focus_descr',
            'eventName'              => FocusEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'               => 'focusshow_list',
            'formTypeOptions'        => ['update_select' => 'campaignevent_properties_focus'],
            'connectionRestrictions' => [
                'anchor' => [
                    'decision.inaction',
                ],
                'source' => [
                    'decision' => [
                        'page.pagehit',
                    ],
                ],
            ],
        ];
        $event->addAction('focus.show', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $focusId = (int) $event->getConfig()['focus'];
        if (!$focusId) {
            return $event->setResult(false);
        }
        $values                 = [];
        $values['focus_item'][] = ['id' => $focusId, 'js' => $this->router->generate('mautic_focus_generate', ['id' => $focusId], true)];
        $this->trackingHelper->updateSession($values);

        return $event->setResult(true);
    }
}
