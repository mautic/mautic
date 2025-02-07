<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadModel $leadModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PointEvents::TRIGGER_ON_BUILD                => ['onTriggerBuild', 0],
            PointEvents::TRIGGER_ON_EVENT_EXECUTE        => ['onTriggerExecute', 0],
            PointEvents::TRIGGER_ON_LEAD_SEGMENTS_CHANGE => ['onLeadSegmentsChange', 0],
        ];
    }

    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $event->addEvent(
            'lead.changelists',
            [
                'group'       => 'mautic.lead.point.trigger',
                'label'       => 'mautic.lead.point.trigger.changelists',
                'eventName'   => PointEvents::TRIGGER_ON_LEAD_SEGMENTS_CHANGE,
                'formType'    => ListActionType::class,
            ]
        );

        $event->addEvent(
            'lead.changetags',
            [
                'group'     => 'mautic.lead.point.trigger',
                'label'     => 'mautic.lead.lead.events.changetags',
                'formType'  => ModifyLeadTagsType::class,
                'eventName' => PointEvents::TRIGGER_ON_EVENT_EXECUTE,
            ]
        );
    }

    public function onTriggerExecute(TriggerExecutedEvent $event): void
    {
        if ('lead.changetags' !== $event->getTriggerEvent()->getType()) {
            return;
        }

        $properties = $event->getTriggerEvent()->getProperties();
        $addTags    = $properties['add_tags'] ?: [];
        $removeTags = $properties['remove_tags'] ?: [];

        if ($this->leadModel->modifyTags($event->getLead(), $addTags, $removeTags)) {
            $event->setSucceded();
        }
    }

    public function onLeadSegmentsChange(TriggerExecutedEvent $event): void
    {
        $lead = $event->getLead();

        $properties = $event->getTriggerEvent()->getProperties();
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        if (!empty($addTo)) {
            $this->leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $this->leadModel->removeFromLists($lead, $removeFrom);
        }
    }
}
