<?php

namespace Mautic\LeadBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBatchLeadChangeEvent;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CampaignBundle\Event\CampaignSingleLeadChangeEvent;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

final class TimelineEventLogCampaignSubscriber
{
    use TimelineEventLogTrait;

    public function __construct(
        LeadEventLogRepository $eventLogRepository,
        private readonly UserHelper $userHelper,
        Translator $translator,
    ) {
        $this->eventLogRepository = $eventLogRepository;
        $this->translator         = $translator;
    }

    #[AsEventListener(event: CampaignSingleLeadChangeEvent::class)]
    public function onChange(CampaignLeadChangeEvent $event): void
    {
        if (!$contact = $event->getLead()) {
            return;
        }

        $this->writeEntries(
            [$contact],
            $event->getCampaign(),
            $event->getAction()
        );
    }

    #[AsEventListener(event: CampaignBatchLeadChangeEvent::class)]
    public function onBatchChange(CampaignLeadChangeEvent $event): void
    {
        if (!$contacts = $event->getLeads()) {
            return;
        }

        $this->writeEntries(
            $contacts,
            $event->getCampaign(),
            $event->getAction()
        );
    }

    #[AsEventListener(event: LeadTimelineEvent::class)]
    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addEvents(
            $event,
            'campaign_membership',
            'mautic.lead.timeline.campaign_membership',
            'ri-time-line',
            'campaign',
            'campaign'
        );
    }

    /**
     * @param Lead[] $contacts
     */
    private function writeEntries(array $contacts, Campaign $campaign, ?string $action): void
    {
        $user = $this->userHelper->getUser();

        $logs = [];
        foreach ($contacts as $contact) {
            $log = new LeadEventLog();
            $log->setUserId($user->getId())
                ->setUserName($user->getUserIdentifier() ?: $this->translator->trans('mautic.core.system'))
                ->setLead($contact)
                ->setBundle('campaign')
                ->setAction($action)
                ->setObject('campaign')
                ->setObjectId($campaign->getId())
                ->setProperties(
                    [
                        'campaign_id'        => $campaign->getId(),
                        'campaign_name'      => $campaign->getName(),
                        'object_description' => $campaign->getName(),
                    ]
                );

            $logs[] = $log;
        }

        $this->eventLogRepository->saveEntities($logs);
        $this->eventLogRepository->detachEntities($logs);
    }
}
