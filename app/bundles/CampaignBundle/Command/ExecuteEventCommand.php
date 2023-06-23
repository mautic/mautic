<?php

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExecuteEventCommand extends Command
{
    use WriteCountTrait;

    /**
     * @var ScheduledExecutioner
     */
    private $scheduledExecutioner;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    public function __construct(ScheduledExecutioner $scheduledExecutioner, TranslatorInterface $translator, FormatterHelper $formatterHelper)
    {
        parent::__construct();

        $this->scheduledExecutioner = $scheduledExecutioner;
        $this->translator           = $translator;
        $this->formatterHelper      = $formatterHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:execute')
            ->addOption(
                '--scheduled-log-ids',
                null,
                InputOption::VALUE_REQUIRED,
                'CSV of specific scheduled log IDs to execute.'
            );

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $ids     = $this->formatterHelper->simpleCsvToArray($input->getOption('scheduled-log-ids'), 'int');
        $counter = $this->scheduledExecutioner->executeByIds($ids, $output);

        $this->writeCounts($output, $this->translator, $counter);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
    protected static $defaultDescription = 'Execute specific scheduled events.';
}
