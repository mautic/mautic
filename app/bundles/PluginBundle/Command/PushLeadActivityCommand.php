<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:integration:pushleadactivity',
    description: 'Push lead activity to integration.',
    aliases: [
        'mautic:integration:pushactivity',
    ]
)]
class PushLeadActivityCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[\Symfony\Component\Console\Attribute\Option(name: '--integration', shortcut: '-i', mode: InputOption::VALUE_REQUIRED, description: 'Integration name. Integration must be enabled and authorised.')]
        $integration = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--start-date', shortcut: '-d', mode: InputOption::VALUE_REQUIRED, description: 'Set start date for updated values.')]
        $startDate = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--end-date', shortcut: '-t', mode: InputOption::VALUE_REQUIRED, description: 'Set end date for updated values.')]
        $endDate = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--time-interval', shortcut: '-a', mode: InputOption::VALUE_OPTIONAL, description: 'Send time interval to check updates on Salesforce, it should be a correct php formatted time interval in the past eg:(-10 minutes)')]
        $timeInterval = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--force', shortcut: '-f', mode: InputOption::VALUE_NONE, description: 'Force execution even if another process is assumed running.')]
        bool $force = false,
    ): int {
        $interval    = $timeInterval;

        if (!$interval) {
            $interval = '15 minutes';
        }
        if (!$startDate) {
            $startDate = date('c', strtotime('-'.$interval));
        }

        if (!$endDate) {
            $endDate = date('c');
        }

        if ($integration) {
            $integrationObject = $this->integrationHelper->getIntegrationObject($integration);

            if (null !== $integrationObject && method_exists($integrationObject, 'pushLeadActivity')) {
                $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.push.leads.activity', ['%integration%' => $integration]).'</info>');

                $params['start'] = $startDate;
                $params['end']   = $endDate;

                $processed = intval($integrationObject->pushLeadActivity($params));

                $output->writeln('<comment>'.$this->translator->trans('mautic.plugin.command.push.leads.events_executed', ['%events%' => $processed]).'</comment>'."\n");
            }
        }

        return Command::SUCCESS;
    }
}
