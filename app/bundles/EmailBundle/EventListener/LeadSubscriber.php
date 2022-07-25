<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Entity\EmailReplyRepositoryInterface;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailReplyRepositoryInterface
     */
    private $emailReplyRepository;

    /**
     * @var StatRepository
     */
    private $statRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        EmailReplyRepositoryInterface $emailReplyRepository,
        StatRepository $statRepository,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->emailReplyRepository = $emailReplyRepository;
        $this->statRepository       = $statRepository;
        $this->translator           = $translator;
        $this->router               = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
        ];
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEmailEvents($event, 'read');
        $this->addEmailEvents($event, 'sent');
        $this->addEmailEvents($event, 'failed');
        $this->addEmailReplies($event);
    }

    public function onLeadMerge(LeadMergeEvent $event)
    {
        $this->statRepository->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }

    /**
     * @param $state
     */
    private function addEmailEvents(LeadTimelineEvent $event, $state)
    {
        // Set available event types
        $eventTypeKey  = 'email.'.$state;
        $eventTypeName = $this->translator->trans('mautic.email.'.$state);
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('emailList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $queryOptions          = $event->getQueryOptions();
        $queryOptions['state'] = $state;
        $stats                 = $this->statRepository->getLeadStats($event->getLeadId(), $queryOptions);

        // Add total to counter
        $event->addToCounter($eventTypeKey, $stats);

        if (!$event->isEngagementCount()) {
            // Add the events to the event array
            foreach ($stats['results'] as $stat) {
                if (!empty($stat['email_name'])) {
                    $label = $stat['email_name'];
                } elseif (!empty($stat['storedSubject'])) {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email').': '.$stat['storedSubject'];
                } else {
                    $label = $this->translator->trans('mautic.email.timeline.event.custom_email');
                }

                if (!empty($stat['idHash'])) {
                    $eventName = [
                        'label'      => $label,
                        'href'       => $this->router->generate('mautic_email_webview', ['idHash' => $stat['idHash']]),
                        'isExternal' => true,
                    ];
                } else {
                    $eventName = $label;
                }
                if ('failed' == $state or 'sent' == $state) { //this is to get the correct column for date dateSent
                    $dateSent = 'sent';
                } else {
                    $dateSent = 'read';
                }

                $contactId = $stat['lead_id'];
                unset($stat['lead_id']);
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$stat['id'],
                        'eventLabel' => $eventName,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $stat['date'.ucfirst($dateSent)],
                        'extra'      => [
                            'stat' => $stat,
                            'type' => $state,
                        ],
                        'contentTemplate' => 'MauticEmailBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => ('read' == $state) ? 'fa-envelope-o' : 'fa-envelope',
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }

    private function addEmailReplies(LeadTimelineEvent $event)
    {
        $eventTypeKey  = 'email.replied';
        $eventTypeName = $this->translator->trans('mautic.email.replied');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('emailList');

        // Decide if those events are filtered
        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $options          = $event->getQueryOptions();
        $replies          = $this->emailReplyRepository->getByLeadIdForTimeline($event->getLeadId(), $options);
        if (!$event->isEngagementCount()) {
            foreach ($replies['results'] as $reply) {
                $label = $this->translator->trans('mautic.email.timeline.event.email_reply');
                if (!empty($reply['email_name'])) {
                    $label .= ': '.$reply['email_name'];
                } elseif (!empty($reply['storedSubject'])) {
                    $label .= ': '.$reply['storedSubject'];
                }

                $contactId = $reply['lead_id'];
                unset($reply['lead_id']);

                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$reply['id'],
                        'eventLabel' => $label,
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $reply['date_replied'],
                        'icon'       => 'fa-envelope',
                        'contactId'  => $contactId,
                    ]
                );
            }
        }
    }
}
