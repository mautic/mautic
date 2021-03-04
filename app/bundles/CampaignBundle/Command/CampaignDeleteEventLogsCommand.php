<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

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

    public function __construct(LeadEventLogRepository $leadEventLogRepository)
    {
        $this->leadEventLogRepository = $leadEventLogRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete campaign event logs')
            ->addArgument(
                'campaign_event_ids',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
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
        $this->leadEventLogRepository->removeEventLogs($eventIds, $campaignId);

        return ExitCode::SUCCESS;
    }
}
