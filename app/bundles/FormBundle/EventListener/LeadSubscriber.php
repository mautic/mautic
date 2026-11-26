<?php

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Event\ContactPreCleanupDeleteEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FormModel $formModel,
        private PageModel $pageModel,
        private SubmissionRepository $submissionRepository,
        private TranslatorInterface $translator,
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
            LeadEvents::LEAD_PRE_DELETE         => ['onLeadPreDelete', 0],
            LeadEvents::LEAD_PRE_CLEANUP_DELETE => ['onLeadPreCleanupDelete', 0],
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
                        'icon'            => 'ri-edit-2-line',
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

    /**
     * Records the contact in its submissions before the foreign key sets lead_id to NULL,
     * so that mautic:forms:delete-submissions-of-deleted-contacts can find them.
     */
    public function onLeadPreDelete(LeadEvent $event): void
    {
        $this->submissionRepository->recordDeletedContacts([$event->getLead()->getId()]);
    }

    /**
     * Only the GDPR cleanup deletes identified contacts, so only this cleanup records the contacts.
     * The regular cleanup deletes anonymous visitors, and their answers, for example to a survey, are kept.
     * It also removes a record that an earlier failed deletion left, so that this record cannot delete these answers.
     */
    public function onLeadPreCleanupDelete(ContactPreCleanupDeleteEvent $event): void
    {
        if ($event->isGdpr()) {
            $this->submissionRepository->recordDeletedContacts($event->getContactIds());

            return;
        }

        $this->submissionRepository->removeDeletedContactRecords($event->getContactIds());
    }
}
