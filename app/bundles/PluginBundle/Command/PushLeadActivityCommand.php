<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PushLeadActivityCommand.
 */
class PushLeadActivityCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory = $container->get('mautic.factory');

        $translator  = $factory->getTranslator();
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
            /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
            $integrationHelper = $factory->getHelper('integration');

            $integrationObject = $integrationHelper->getIntegrationObject($integration);

            if ($integrationObject !== null && method_exists($integrationObject, 'pushLeadActivity')) {
                $config = $integrationObject->mergeConfigToFeatureSettings();

                $filters = [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ];
                if (isset($config['includeEvents']) and !empty($config['includeEvents'])) {
                    $filters['includeEvents'] = explode(',', $config['includeEvents']);
                }

                if (isset($config['excludeEvents']) and !empty($config['excludeEvents'])) {
                    $filters['excludeEvents'] = explode(',', $config['excludeEvents']);
                }
                $output->writeln('<info>'.$translator->trans('mautic.plugin.command.push.leads.activity', ['%integration%' => $integration]).'</info>');

                $params['start']   = $startDate;
                $params['end']     = $endDate;
                $params['filters'] = $filters;

                $processed = intval($integrationObject->pushLeadActivity($params));

                $output->writeln('<comment>'.$translator->trans('mautic.plugin.command.push.leads.events_executed', ['%events%' => $processed]).'</comment>'."\n");
            }
        }

        return 0;
    }
}
