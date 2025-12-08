<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: CampaignDeleteEventLogsCommand::COMMAND_NAME,
    description: 'Delete campaign event logs'
)]
class CampaignDeleteEventLogsCommand
{
    public const COMMAND_NAME = 'mautic:campaign:delete-event-logs';

    public function __construct(private LeadEventLogRepository $leadEventLogRepository, private CampaignModel $campaignModel, private EventModel $eventModel)
    {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Argument(name: 'campaign_event_ids', description: 'Campaign event ids to delete event logs.')]
        array $campaignEventIds,
        #[\Symfony\Component\Console\Attribute\Option(name: '--campaign-id', shortcut: '-i', description: 'Delete campaign also otherwise will delete event and event log only.')]
        ?int $campaignId = null,
    ): int {
        $eventIds   = $campaignEventIds;
        $campaignId = (int) $campaignId;

        if (!empty($campaignId)) {
            // For entire campaign deletion, remove both events and logs
            $this->leadEventLogRepository->removeEventLogsByCampaignId($campaignId);
            $this->eventModel->deleteEventsByCampaignId($campaignId);
            $campaign = $this->campaignModel->getEntity($campaignId);
            $this->campaignModel->deleteCampaign($campaign);
        } elseif (!empty($eventIds)) {
            // For individual event deletion, just soft-delete the event but keep logs
            $this->eventModel->deleteEventsByEventIds($eventIds);
        }

        return Command::SUCCESS;
    }
}
