<?php

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FormModel $formModel,
        private PageModel $pageModel,
        private SubmissionRepository $submissionRepository,
        private TranslatorInterface $translator,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        // Set available event types
        $eventTypeKey  = 'form.submitted';
        $eventTypeName = $this->translator->trans('mautic.form.event.submitted');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup(['formList', 'submissionEventDetails']);

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $rows = $this->submissionRepository->getSubmissions($event->getQueryOptions());

        // Add total to counter
        $event->addToCounter($eventTypeKey, $rows);

        if (!$event->isEngagementCount()) {
            // Add the submissions to the event array
            foreach ($rows['results'] as $row) {
                // Convert to local from UTC
                $form       = $this->formModel->getEntity($row['form_id']);
                $submission = $this->submissionRepository->getEntity($row['id']);

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$row['id'],
                        'eventLabel' => [
                            'label' => $form->getName(),
                            'href'  => $this->router->generate('mautic_form_action', ['objectAction' => 'view', 'objectId' => $form->getId()]),
                        ],
                        'eventType' => $eventTypeName,
                        'timestamp' => $row['dateSubmitted'],
                        'extra'     => [
                            'submission' => $submission,
                            'form'       => $form,
                            'page'       => $this->pageModel->getEntity($row['page_id']),
                        ],
                        'contentTemplate' => '@MauticForm/SubscribedEvents/Timeline/index.html.twig',
                        'icon'            => 'fa-pencil-square-o',
                        'contactId'       => $row['lead_id'],
                    ]
                );
            }
        }
    }

    public function onLeadMerge(LeadMergeEvent $event): void
    {
        $this->submissionRepository->updateLead($event->getLoser()->getId(), $event->getVictor()->getId());
    }
}
