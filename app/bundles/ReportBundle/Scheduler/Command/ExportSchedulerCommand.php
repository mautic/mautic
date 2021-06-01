<?php

namespace Mautic\ReportBundle\Scheduler\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExportSchedulerCommand extends ModeratedCommand
{
    /**
     * @var ReportExporter
     */
    private $reportExporter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ReportExporter $reportExporter, TranslatorInterface $translator)
    {
        parent::__construct();
        $this->reportExporter = $reportExporter;
        $this->translator     = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:reports:scheduler')
            ->setDescription('Processes scheduler for report\'s export')
            ->addOption('--report', 'report', InputOption::VALUE_OPTIONAL, 'ID of report. Process all reports if not set.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $report = $input->getOption('report');

        try {
            $exportOption = new ExportOption($report);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$this->translator->trans('mautic.report.schedule.command.invalid_parameter').'</error>');

            return 1;
        }

        if (!$this->checkRunStatus($input, $output, $exportOption->getReportId())) {
            return 0;
        }

        try {
            $this->reportExporter->processExport($exportOption);

            $output->writeln('<info>'.$this->translator->trans('mautic.report.schedule.command.finished').'</info>');

            $this->completeRun();

            return 0;
        } catch (FileIOException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1;
        }
    }
}
