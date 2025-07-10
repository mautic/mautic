<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CampaignDeleteEventLogsCommand extends Command
{
    protected static $defaultDescription = 'Delete campaign event logs';
    /**
     * @var string
     */
    public const COMMAND_NAME = 'mautic:campaign:delete-event-logs';

    public function __construct(private LeadEventLogRepository $leadEventLogRepository, private CampaignModel $campaignModel, private EventModel $eventModel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                'campaign_event_ids',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Campaign event ids to delete event logs.'
            )
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Delete campaign also otherwise will delete event and event log only.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventIds   = $input->getArgument('campaign_event_ids');
        $campaignId = (int) $input->getOption('campaign-id');

        if (!empty($campaignId)) {
            $this->leadEventLogRepository->removeEventLogsByCampaignId($campaignId);
            $this->eventModel->deleteEventsByCampaignId($campaignId);
            $campaign = $this->campaignModel->getEntity($campaignId);
            $this->campaignModel->deleteCampaign($campaign);
        } elseif (!empty($eventIds)) {
            $this->leadEventLogRepository->removeEventLogs($eventIds);
            $this->eventModel->deleteEventsByEventIds($eventIds);
        }

        return Command::SUCCESS;
    }
}
