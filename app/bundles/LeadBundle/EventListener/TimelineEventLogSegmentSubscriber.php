<?php

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimelineEventLogSegmentSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    public function __construct(
        LeadEventLogRepository $eventLogRepository,
        private UserHelper $userHelper,
        Translator $translator,
        private EntityManagerInterface $em,
    ) {
        $this->eventLogRepository = $eventLogRepository;
        $this->translator         = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_LIST_CHANGE       => 'onChange',
            LeadEvents::LEAD_LIST_BATCH_CHANGE => 'onBatchChange',
            LeadEvents::TIMELINE_ON_GENERATE   => 'onTimelineGenerate',
        ];
    }

    public function onChange(ListChangeEvent $event): void
    {
        if (!$contact = $event->getLead()) {
            return;
        }

        $this->writeEntries(
            [$contact],
            $event->getList(),
            $event->wasAdded() ? 'added' : 'removed',
            $event->getDate()
        );
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'segment_membership',
            'mautic.lead.timeline.segment_membership',
            'ri-pie-chart-line',
            'lead',
            'segment'
        );
    }

    public function onBatchChange(ListChangeEvent $event): void
    {
        if (!$contacts = $event->getLeads()) {
            return;
        }

        $this->writeEntries(
            $contacts,
            $event->getList(),
            $event->wasAdded() ? 'added' : 'removed',
            $event->getDate()
        );
    }

    private function writeEntries(array $contacts, LeadList $segment, $action, \DateTime $date = null): void
    {
        $user                    = $this->userHelper->getUser();
        $logs                    = [];
        $detachContactReferences = false;

        foreach ($contacts as $key => $contact) {
            if (!$contact instanceof Lead) {
                $id                      = is_array($contact) ? $contact['id'] : $contact;
                $contact                 = $this->em->getReference(Lead::class, $id);
                $contacts[$key]          = $contact;
                $detachContactReferences = true;
            }

            $log = new LeadEventLog();
            $log->setUserId($user->getId())
                ->setUserName($user->getUserIdentifier() ?: $this->translator->trans('mautic.core.system'))
                ->setLead($contact)
                ->setBundle('lead')
                ->setAction($action)
                ->setObject('segment')
                ->setObjectId($segment->getId())
                ->setProperties(
                    [
                        'object_description' => $segment->getName(),
                    ]
                );

            if ($date) {
                $log->setDateAdded($date);
            }

            $logs[] = $log;
        }

        $this->eventLogRepository->saveEntities($logs);
        $this->eventLogRepository->detachEntities($logs);

        if ($detachContactReferences) {
            foreach ($contacts as $contact) {
                $this->em->detach($contact);
            }
        }
    }
}
