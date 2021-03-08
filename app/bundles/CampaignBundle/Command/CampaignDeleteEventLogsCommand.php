<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Helper\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CampaignDeleteEventLogsCommand extends Command
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'mautic:campaign:delete-event-logs';

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(LeadEventLogRepository $leadEventLogRepository, CampaignRepository $campaignRepository, EventRepository $eventRepository)
    {
        $this->leadEventLogRepository = $leadEventLogRepository;
        $this->campaignRepository     = $campaignRepository;
        $this->eventRepository        = $eventRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete campaign event logs')
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
            $this->eventRepository->deleteEventsByCampaignId($campaignId);
            $this->campaignRepository->deleteCampaign($campaignId);
        } elseif (!empty($eventIds)) {
            $this->leadEventLogRepository->removeEventLogs($eventIds);
            $this->eventRepository->deleteEventsByEventsIds($eventIds);
        }

        return ExitCode::SUCCESS;
    }
}
