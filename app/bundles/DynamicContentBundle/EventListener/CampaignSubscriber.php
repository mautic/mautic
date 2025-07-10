<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentDecisionType;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentSendType;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        protected CacheProvider $cache,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['onCampaignBuild', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
            DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION   => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'dwc.push_content',
            [
                'label'                  => 'mautic.dynamicContent.campaign.send_dwc',
                'description'            => 'mautic.dynamicContent.campaign.send_dwc.tooltip',
                'eventName'              => DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'               => DynamicContentSendType::class,
                'formTypeOptions'        => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme'              => '@MauticDynamicContent/FormTheme/DynamicContentPushList/_dynamiccontentpush_list_row.html.twig',
                'timelineTemplate'       => '@MauticDynamicContent/SubscribedEvents/Timeline/index.html.twig',
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
                'formType'        => DynamicContentDecisionType::class,
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_dynamicContent'],
                'formTheme'       => '@MauticDynamicContent/FormTheme/DynamicContentDecisionList/_dynamiccontentdecision_list_row.html.twig',
                'channel'         => 'dynamicContent',
                'channelIdField'  => 'dynamicContent',
            ]
        );
    }

    /**
     * @return false|CampaignExecutionEvent
     *
     * @throws InvalidArgumentException
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventConfig  = $event->getConfig();
        $eventDetails = $event->getEventDetails();
        $lead         = $event->getLead();

        // stop
        if ($eventConfig['dwc_slot_name'] !== $eventDetails) {
            $event->setResult(false);

            return false;
        }

        $defaultDwc = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);

        if ($defaultDwc instanceof DynamicContent) {
            // Set the default content in case none of the actions return data
            $this->dynamicContentModel->setSlotContentForLead($defaultDwc, $lead, $eventDetails);
        }

        $item = $this->cache->getItem('dwc.slot_name.lead.'.$lead->getId());
        $item->set($eventDetails);
        $item->expiresAfter(86400); // one day in seconds
        $this->cache->save($item);

        $event->stopPropagation();

        return $event->setResult(true);
    }

    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $eventConfig = $event->getConfig();
        $lead        = $event->getLead();
        /* @var CacheItemInterface $item */
        $item = $this->cache->getItem('dwc.slot_name.lead.'.$lead->getId());
        $slot = $item->get();

        $dwc = $this->dynamicContentModel->getRepository()->getEntity($eventConfig['dynamicContent']);

        if ($dwc instanceof DynamicContent) {
            // Use translation if available
            list($ignore, $dwc) = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);
            \assert($dwc instanceof DynamicContent);

            if ($slot) {
                $this->dynamicContentModel->setSlotContentForLead($dwc, $lead, $slot);
            }

            $stat = $this->dynamicContentModel->createStatEntry($dwc, $lead, $slot);

            $tokenEvent = new TokenReplacementEvent($dwc->getContent(), $lead, ['slot' => $slot, 'dynamic_content_id' => $dwc->getId()]);
            $tokenEvent->setStat($stat);
            $this->dispatcher->dispatch($tokenEvent, DynamicContentEvents::TOKEN_REPLACEMENT);

            $content = $tokenEvent->getContent();
            $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);

            $event->stopPropagation();

            $result = $event->setResult($content);
            $event->setChannel('dynamicContent', $dwc->getId());

            return $result;
        }
    }
}
