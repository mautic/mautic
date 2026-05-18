<?php

namespace Mautic\ReportBundle\Scheduler\Command;

use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:reports:scheduler',
    description: "Processes scheduler for report's export"
)]
class ExportSchedulerCommand extends Command
{
    public function __construct(
        private ReportExporter $reportExporter,
        private ReportCleanup $reportCleanup,
        private TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('--report', 'report', InputOption::VALUE_OPTIONAL, 'ID of report. Process all reports if not set.');
        $this->addOption('--cleanup-only', 'co', InputOption::VALUE_NONE, 'Only cleanup old files without processing new export.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report      = $input->getOption('report');
        $cleanupOnly = $input->getOption('cleanup-only') ?? false;

        if (!is_null($report) && !is_numeric($report)) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return Command::INVALID;
        }

        try {
            $exportOption = new ExportOption((int) $report);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return Command::FAILURE;
        }

        try {
            if ($exportOption->getReportId()) {
                $this->reportCleanup->cleanup($exportOption->getReportId());
            } else {
                $this->reportCleanup->cleanupAll();
            }

            if ($cleanupOnly) {
                return Command::SUCCESS;
            }

            $this->reportExporter->processExport($exportOption);

            $output->writeln('<info>'.$this->translator->trans('mautic.report.schedule.command.finished').'</info>');
        } catch (FileIOException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
