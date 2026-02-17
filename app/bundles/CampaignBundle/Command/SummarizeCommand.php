<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SummarizeCommand extends ModeratedCommand
{
    use WriteCountTrait;

    public const NAME = 'mautic:campaigns:summarize';

    public function __construct(
        private TranslatorInterface $translator,
        private SummaryModel $summaryModel,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
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
            );

        parent::configure();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->checkRunStatus($input, $output)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $batchLimit = (int) $input->getOption('batch-limit');
        $maxHours   = (int) $input->getOption('max-hours');
        $rebuild    = (bool) $input->getOption('rebuild');

        $output->writeln(
            "<info>{$this->translator->trans('mautic.campaign.summarizing', ['%batch%' => $batchLimit])}</info>"
        );

        $this->summaryModel->summarize($output, $batchLimit, $maxHours, $rebuild);

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Builds historical campaign summary statistics if they do not already exist.';
}
