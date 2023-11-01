<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Command;

use Doctrine\DBAL\DBALException;
use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SummarizeCommand extends ModeratedCommand
{
    use WriteCountTrait;

    public const NAME = 'mautic:campaigns:summarize';

    /**
     * @var SummaryModel
     */
    private $summaryModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        TranslatorInterface $translator,
        SummaryModel $summaryModel
    ) {
        parent::__construct();

        $this->translator   = $translator;
        $this->summaryModel = $summaryModel;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Number of hours to process per batch. 1 hour is default value.',
                '1'
            )
            ->addOption(
                '--max-hours',
                null,
                InputOption::VALUE_OPTIONAL,
                'Optionally specify how many hours back in time you wish to summarize.'
            )
            ->addOption(
                '--rebuild',
                null,
                InputOption::VALUE_NONE,
                'Rebuild existing data. To be used only if database exceptions have been known to cause inaccuracies.'
            )
            ->setDescription('Builds historical campaign summary statistics if they do not already exist.');

        parent::configure();
    }

    /**
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->checkRunStatus($input, $output)) {
            return 0;
        }

        $batchLimit = (int) $input->getOption('batch-limit');
        $maxHours   = (int) $input->getOption('max-hours');
        $rebuild    = (bool) $input->getOption('rebuild');

        $output->writeln(
            "<info>{$this->translator->trans('mautic.campaign.summarizing', ['%batch%' => $batchLimit])}</info>"
        );

        $this->summaryModel->summarize($output, $batchLimit, $maxHours, $rebuild);

        $this->completeRun();

        return 0;
    }
}
