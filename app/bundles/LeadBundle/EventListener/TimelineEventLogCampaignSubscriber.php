<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TimelineEventLogCampaignSubscriber implements EventSubscriberInterface
{
    use TimelineEventLogTrait;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * TimelineEventLogCampaignSubscriber constructor.
     *
     * @param LeadEventLogRepository $eventLogRepository
     * @param UserHelper             $userHelper
     * @param TranslatorInterface    $translator
     */
    public function __construct(LeadEventLogRepository $eventLogRepository, UserHelper $userHelper, TranslatorInterface $translator)
    {
        $this->eventLogRepository = $eventLogRepository;
        $this->userHelper         = $userHelper;
        $this->translator         = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE     => 'onChange',
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE => 'onBatchChange',
            LeadEvents::TIMELINE_ON_GENERATE           => 'onTimelineGenerate',
        ];
    }

    /**
     * @param CampaignLeadChangeEvent $event
     */
    public function onChange(CampaignLeadChangeEvent $event)
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

    /**
     * @param CampaignLeadChangeEvent $event
     */
    public function onBatchChange(CampaignLeadChangeEvent $event)
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

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $this->addEvents(
            $event,
            'campaign_membership',
            'mautic.lead.timeline.campaign_membership',
            'fa-clock-o',
            'campaign',
            'campaign'
        );
    }

    /**
     * @param Lead[]   $contacts
     * @param Campaign $campaign
     * @param          $action
     */
    private function writeEntries(array $contacts, Campaign $campaign, $action)
    {
        $user = $this->userHelper->getUser();

        $logs = [];
        foreach ($contacts as $contact) {
            $log = new LeadEventLog();
            $log->setUserId($user->getId())
                ->setUserName($user->getUsername() ?: $this->translator->trans('mautic.core.system'))
                ->setLead($contact)
                ->setBundle('campaign')
                ->setAction($action)
                ->setObject('campaign')
                ->setObjectId($campaign->getId())
                ->setProperties(
                    [
                        'object_description' => $campaign->getName(),
                    ]
                );

            $logs[] = $log;
        }

        $this->eventLogRepository->saveEntities($logs);
        $this->eventLogRepository->clear();
    }
}
