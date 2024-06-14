<?php

namespace Mautic\ReportBundle\Scheduler\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportSchedulerCommand extends ModeratedCommand
{
    public const NAME = 'mautic:reports:scheduler';

    public function __construct(
        private ReportExporter $reportExporter,
        private ReportCleanup $reportCleanup,
        private TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption('--report', 'report', InputOption::VALUE_OPTIONAL, 'ID of report. Process all reports if not set.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $input->getOption('report');

        try {
            $exportOption = new ExportOption($report);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return Command::SUCCESS;
        }

        if (!$this->checkRunStatus($input, $output, $exportOption->getReportId())) {
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
            $this->completeRun();

            return Command::SUCCESS;
        } catch (FileIOException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }

        return Command::FAILURE;
    }

    protected static $defaultDescription = 'Processes scheduler for report\'s export';
}
