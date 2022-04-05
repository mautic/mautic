<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PushLeadActivityCommand extends Command
{
    private TranslatorInterface $translator;
    private IntegrationHelper $integrationHelper;

    public function __construct(TranslatorInterface $translator, IntegrationHelper $integrationHelper)
    {
        parent::__construct();

        $this->translator        = $translator;
        $this->integrationHelper = $integrationHelper;
    }

    protected function configure()
    {
        $this
            ->setName('mautic:integration:pushleadactivity')
            ->setAliases(
                [
                    'mautic:integration:pushactivity',
                ]
            )
            ->setDescription('Push lead activity to integration.')
            ->addOption(
                '--integration',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Integration name. Integration must be enabled and authorised.',
                null
            )
            ->addOption('--start-date', '-d', InputOption::VALUE_REQUIRED, 'Set start date for updated values.')
            ->addOption(
                '--end-date',
                '-t',
                InputOption::VALUE_REQUIRED,
                'Set end date for updated values.'
            )
            ->addOption(
                '--time-interval',
                '-a',
                InputOption::VALUE_OPTIONAL,
                'Send time interval to check updates on Salesforce, it should be a correct php formatted time interval in the past eg:(-10 minutes)'
            )
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $integration = $input->getOption('integration');
        $startDate   = $input->getOption('start-date');
        $endDate     = $input->getOption('end-date');
        $interval    = $input->getOption('time-interval');

        if (!$interval) {
            $interval = '15 minutes';
        }
        if (!$startDate) {
            $startDate = date('c', strtotime('-'.$interval));
        }

        if (!$endDate) {
            $endDate = date('c');
        }

        if ($integration && $startDate && $endDate) {
            $integrationObject = $this->integrationHelper->getIntegrationObject($integration);

            if (null !== $integrationObject && method_exists($integrationObject, 'pushLeadActivity')) {
                $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.push.leads.activity', ['%integration%' => $integration]).'</info>');

                $params['start'] = $startDate;
                $params['end']   = $endDate;

                $processed = intval($integrationObject->pushLeadActivity($params));

                $output->writeln('<comment>'.$this->translator->trans('mautic.plugin.command.push.leads.events_executed', ['%events%' => $processed]).'</comment>'."\n");
            }
        }

        return 0;
    }
}
