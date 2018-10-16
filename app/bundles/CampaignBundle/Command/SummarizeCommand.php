<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SummaryFillCommand.
 */
class SummarizeCommand extends ModeratedCommand
{
    use WriteCountTrait;

    /**
     * @var SummaryModel
     */
    private $summaryModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * SummarizeCommand constructor.
     *
     * @param TranslatorInterface $translator
     * @param FormatterHelper     $formatterHelper
     * @param SummaryModel        $summaryModel
     */
    public function __construct(
        TranslatorInterface $translator,
        FormatterHelper $formatterHelper,
        SummaryModel $summaryModel
    ) {
        parent::__construct();

        $this->translator      = $translator;
        $this->formatterHelper = $formatterHelper;
        $this->summaryModel    = $summaryModel;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:summarize')
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Number of hours to process per batch.',
                1
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->checkRunStatus($input, $output)) {
            return 0;
        }

        $batchLimit = $input->getOption('batch-limit');
        $maxHours   = $input->getOption('max-hours');
        $rebuild    = $input->getOption('rebuild');

        $output->writeln(
            '<info>'.$this->translator->trans('mautic.campaign.summarizing', ['%batch%' => $batchLimit]).'</info>'
        );

        $this->summaryModel->summarize($output, $batchLimit, $maxHours, $rebuild);

        $this->completeRun();

        return 0;
    }
}
