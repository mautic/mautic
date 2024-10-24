<?php

namespace Mautic\ReportBundle\Scheduler\Command;

use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportSchedulerCommand extends Command
{
    public function __construct(
        private ReportExporter $reportExporter,
        private ReportCleanup $reportCleanup,
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mautic:reports:scheduler')
            ->addOption('--report', 'report', InputOption::VALUE_OPTIONAL, 'ID of report. Process all reports if not set.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $input->getOption('report');

        if (!is_null($report) && !is_numeric($report)) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return Command::INVALID;
        }

        try {
            $exportOption = new ExportOption((int) $report);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return Command::SUCCESS;
        }

        try {
            if ($exportOption->getReportId()) {
                $this->reportCleanup->cleanup($exportOption->getReportId());
            } else {
                $this->reportCleanup->cleanupAll();
            }

            $this->reportExporter->processExport($exportOption);

            $output->writeln('<info>'.$this->translator->trans('mautic.report.schedule.command.finished').'</info>');
        } catch (FileIOException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        return Command::SUCCESS;
    }

    protected static $defaultDescription = 'Processes scheduler for report\'s export';
}
