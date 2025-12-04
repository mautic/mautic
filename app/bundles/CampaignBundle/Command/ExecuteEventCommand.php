<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:campaigns:execute',
    description: 'Execute specific scheduled events.'
)]
class ExecuteEventCommand
{
    use WriteCountTrait;

    public function __construct(
        private ScheduledExecutioner $scheduledExecutioner,
        private TranslatorInterface $translator,
        private FormatterHelper $formatterHelper,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: '--scheduled-log-ids', description: 'CSV of specific scheduled log IDs to execute.')]
        $scheduledLogIds,
        #[\Symfony\Component\Console\Attribute\Option(name: '--execution-time', description: 'Scheduled execution time of event log')]
        $executionTime,
        OutputInterface $output,
    ): int {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $now     = empty($executionTime) ? null : new \DateTime($executionTime);
        $ids     = $this->formatterHelper->simpleCsvToArray($scheduledLogIds, 'int');
        $counter = $this->scheduledExecutioner->executeByIds($ids, $output, $now);

        $this->writeCounts($output, $this->translator, $counter);

        return Command::SUCCESS;
    }
}
