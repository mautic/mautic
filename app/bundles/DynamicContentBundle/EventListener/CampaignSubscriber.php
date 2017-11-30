<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var DynamicContentModel
     */
    protected $dynamicContentModel;

    /**
     * @var Session
     */
    protected $session;

    /**
     * CampaignSubscriber constructor.
     *
     * @param LeadModel           $leadModel
     * @param DynamicContentModel $dynamicContentModel
     */
    public function __construct(LeadModel $leadModel, DynamicContentModel $dynamicContentModel, Session $session)
    {
        $this->leadModel           = $leadModel;
        $this->dynamicContentModel = $dynamicContentModel;
        $this->session             = $session;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['onCampaignBuild', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION   => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $event->addAction(
            'dwc.push_content',
            [
                'label'                  => 'mautic.dynamicContent.campaign.send_dwc',
                'description'            => 'mautic.dynamicContent.campaign.send_dwc.tooltip',
                'eventName'              => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'               => 'dwcsend_list',
                'formTypeOptions'        => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme'              => 'MauticDynamicContentBundle:FormTheme\DynamicContentPushList',
                'timelineTemplate'       => 'MauticDynamicContentBundle:SubscribedEvents\Timeline:index.html.php',
                'hideTriggerMode'        => true,
                'connectionRestrictions' => [
                    'anchor' => [
                        'decision.inaction',
                    ],
                    'source' => [
                        'decision' => [
                            'dwc.decision',
                        ],
                    ],
                ],
                'channel'        => 'dynamicContent',
                'channelIdField' => 'dwc_slot_name',
            ]
        );

        $event->addDecision(
            'dwc.decision',
            [
                'label'           => 'mautic.dynamicContent.campaign.decision_dwc',
                'description'     => 'mautic.dynamicContent.campaign.decision_dwc.tooltip',
                'eventName'       => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'formType'        => 'dwcdecision_list',
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme'       => 'MauticDynamicContentBundle:FormTheme\DynamicContentDecisionList',
                'channel'         => 'dynamicContent',
                'channelIdField'  => 'dynamicContent',
            ]
        );
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventConfig  = $event->getConfig();
        $eventDetails = $event->getEventDetails();
        $lead         = $event->getLead();

        if ($eventConfig['dwc_slot_name'] === $eventDetails) {
            $defaultDwc = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);

            if ($defaultDwc instanceof DynamicContent) {
                // Set the default content in case none of the actions return data
                $this->dynamicContentModel->setSlotContentForLead($defaultDwc, $lead, $eventDetails);
            }

            $this->session->set('dwc.slot_name.lead.'.$lead->getId(), $eventDetails);

            $event->stopPropagation();

            return $event->setResult(true);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $eventConfig = $event->getConfig();
        $lead        = $event->getLead();
        $slot        = $this->session->get('dwc.slot_name.lead.'.$lead->getId());

        $dwc = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);

        if ($dwc instanceof DynamicContent) {
            // Use translation if available
            list($ignore, $dwc) = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);

            if ($slot) {
                $this->dynamicContentModel->setSlotContentForLead($dwc, $lead, $slot);
            }

            $this->dynamicContentModel->createStatEntry($dwc, $lead, $slot);

            $tokenEvent = new TokenReplacementEvent($dwc->getContent(), $lead, ['slot' => $slot, 'dynamic_content_id' => $dwc->getId()]);
            $this->dispatcher->dispatch(DynamicContentEvents::TOKEN_REPLACEMENT, $tokenEvent);

            $content = $tokenEvent->getContent();
            $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);

            $event->stopPropagation();

            $result = $event->setResult($content);
            $event->setChannel('dynamicContent', $dwc->getId());

            return $result;
        }
    }
}
