<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimelineEventLogCampaignSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    public function __construct(
        LeadEventLogRepository $eventLogRepository,
        private UserHelper $userHelper,
        Translator $translator,
    ) {
        $this->eventLogRepository = $eventLogRepository;
        $this->translator         = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE     => 'onChange',
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE => 'onBatchChange',
            LeadEvents::TIMELINE_ON_GENERATE           => 'onTimelineGenerate',
        ];
    }

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
    private function writeEntries(array $contacts, Campaign $campaign, $action): void
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
