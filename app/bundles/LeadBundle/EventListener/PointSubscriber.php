<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\Form\Type\StageType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use Mautic\StageBundle\Model\StageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PointSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadModel $leadModel,
        private StageModel $stageModel,
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

        $stages                  = $this->stageModel->getUserStages();
        $stageListItem           = $this->translator->trans('mautic.lead.stage.remove');
        $choices[$stageListItem] = 0;
        foreach ($stages as $stage) {
            $choices[$stage['name']] = $stage['id'];
        }

        $event->addEvent(
            'lead.changestage',
            [
                'group'           => 'mautic.lead.point.trigger',
                'label'           => 'mautic.lead.lead.events.changestage',
                'formType'        => StageType::class,
                'formTypeOptions' => ['items' => $choices],
                'eventName'       => PointEvents::TRIGGER_ON_EVENT_EXECUTE,
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

        if ($this->leadModel->modifyTags($event->getLead(), $addTags, $removeTags)) {
            $event->setSucceded();
        }
    }

    /**
     * Change or remove the stage of a contact based on the trigger event.
     */
    private function handelChangeStage(TriggerExecutedEvent $event): void
    {
        $properties = $event->getTriggerEvent()->getProperties();
        $stageId    = (int) $properties['addstage'];
        $lead       = $event->getLead();

        if (0 === $stageId) {
            $this->handleRemoveStage($lead);
            $event->setSucceded();

            return;
        }

        try {
            $stage = $this->stageModel->getEntity($stageId);
            if (null === $stage || false === $stage->isPublished()) {
                throw new \InvalidArgumentException("Stage for ID $stageId not found");
            }

            $this->leadModel->changeStage(
                $lead,
                $stage,
                $this->translator->trans('mautic.lead.point.trigger')
            );

            $event->setSucceded();
        } catch (\UnexpectedValueException|\InvalidArgumentException $e) {
            $event->setFailed();
            $this->logger->info("LeadBundle: Stage not updated for lead {$event->getLead()->getId()} by trigger because: {$e->getMessage()}");
        }
    }

    private function handleRemoveStage(Lead $lead): void
    {
        $stage = $lead->getStage();

        if (isset($stage)) {
            $this->leadModel->removeFromStage(
                $lead,
                $stage,
                $this->translator->trans('mautic.stage.event.removed.batch')
            );
        }
    }
}
