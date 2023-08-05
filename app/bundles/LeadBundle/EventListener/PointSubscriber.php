<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use Mautic\StageBundle\Form\Type\StageActionChangeType;
use Mautic\StageBundle\Helper\StageHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PointSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadModel $leadModel,
        private StageHelper $stageHelper,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PointEvents::TRIGGER_ON_BUILD         => ['onTriggerBuild', 0],
            PointEvents::TRIGGER_ON_EVENT_EXECUTE => ['onTriggerExecute', 0],
        ];
    }

    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $event->addEvent(
            'lead.changelists',
            [
                'group'    => 'mautic.lead.point.trigger',
                'label'    => 'mautic.lead.point.trigger.changelists',
                'callback' => [\Mautic\LeadBundle\Helper\PointEventHelper::class, 'changeLists'],
                'formType' => ListActionType::class,
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

        $event->addEvent(
            'lead.changestage',
            [
                'group'     => 'mautic.lead.point.trigger',
                'label'     => 'mautic.lead.lead.events.changestage',
                'formType'  => StageActionChangeType::class,
                'eventName' => PointEvents::TRIGGER_ON_EVENT_EXECUTE,
            ]
        );
    }

    public function onTriggerExecute(TriggerExecutedEvent $event): void
    {
        if ('lead.changetags' === $event->getTriggerEvent()->getType()) {
            $this->handelChangeTags($event);
        } elseif ('lead.changestage' === $event->getTriggerEvent()->getType()) {
            $this->handelChangeStage($event);
        }
    }

    /**
     * Add or remove tags from a contact based on the trigger event.
     */
    private function handelChangeTags(TriggerExecutedEvent $event): void
    {
        $properties         = $event->getTriggerEvent()->getProperties();
        $addTags            = $properties['add_tags'] ?: [];
        $removeTags         = $properties['remove_tags'] ?: [];

        $this->leadModel->modifyTags($event->getLead(), $addTags, $removeTags);
    }

    /**
     * Change the stage of a contact based on the trigger event.
     *
     * @todo allow to remove a stage
     */
    private function handelChangeStage(TriggerExecutedEvent $event): void
    {
        $stageId  = (int) $event->getTriggerEvent()->getProperties()['stage'];
        $stage    = $this->stageHelper->getStage($stageId);

        if (null === $stage) {
            throw new \InvalidArgumentException("Stage for ID $stageId not found");
        }
        try {
            $this->stageHelper->changeStage(
                $event->getLead(),
                $stage,
                $this->translator->trans('mautic.lead.point.trigger')
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->info("LeadBundle: Stage not updated for lead {$event->getLead()->getId()} by trigger because: {$e->getMessage()}");
        }
    }
}
