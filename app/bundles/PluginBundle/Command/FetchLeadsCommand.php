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
 * Class FetchLeadsCommand.
 */
class FetchLeadsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:integration:fetchleads')
            ->setAliases(
                [
                    'mautic:integration:synccontacts',
                ]
            )
            ->setDescription('Fetch leads from integration.')
            ->addOption(
                '--integration',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Fetch leads from integration. Integration must be enabled and authorised.',
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
            ->addOption(
                '--limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Number of records to process when syncing objects',
                100
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

        $translator    = $container->get('translator');
        $integration   = $input->getOption('integration');
        $startDate     = $input->getOption('start-date');
        $endDate       = $input->getOption('end-date');
        $interval      = $input->getOption('time-interval');
        $limit         = $input->getOption('limit');
        $leadsExecuted = $contactsExecuted = null;

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
            $integrationHelper = $container->get('mautic.helper.integration');

            $integrationObject = $integrationHelper->getIntegrationObject($integration);
            $config            = $integrationObject->mergeConfigToFeatureSettings();
            $supportedFeatures = $integrationObject->getIntegrationSettings()->getSupportedFeatures();

            if (!isset($config['objects'])) {
                $config['objects'] = [];
            }

            $params['start'] = $startDate;
            $params['end']   = $endDate;
            $params['limit'] = $limit;

            // set this constant to ensure that all contacts have the same date modified time and date synced time to prevent a pull/push loop
            define('MAUTIC_DATE_MODIFIED_OVERRIDE', time());

            if (isset($supportedFeatures) && in_array('get_leads', $supportedFeatures)) {
                if ($integrationObject !== null && method_exists($integrationObject, 'getLeads') && isset($config['objects'])) {
                    $output->writeln('<info>'.$translator->trans('mautic.plugin.command.fetch.leads', ['%integration%' => $integration]).'</info>');
                    if (strtotime($startDate) > strtotime('-30 days')) {
                        $output->writeln('<comment>'.$translator->trans('mautic.plugin.command.fetch.leads.starting').'</comment>');

                        $updated = $created = $processed = 0;
                        if (in_array('Lead', $config['objects'])) {
                            $results = $integrationObject->getLeads($params, null, $leadsExecuted, [], 'Lead');
                            if (is_array($results)) {
                                list($justUpdated, $justCreated) = $results;
                                $updated += intval($justUpdated);
                                $created += intval($justCreated);
                            } else {
                                $processed += intval($results);
                            }
                        }
                        if (in_array('Contact', $config['objects'])) {
                            $results = $integrationObject->getLeads($params, null, $contactsExecuted, [], 'Contact');
                            if (is_array($results)) {
                                list($justUpdated, $justCreated) = $results;
                                $updated += intval($justUpdated);
                                $created += intval($justCreated);
                            } else {
                                $processed += intval($results);
                            }
                        }

                        if ($processed) {
                            $output->writeln(
                                '<comment>'.$translator->trans('mautic.plugin.command.fetch.leads.events_executed', ['%events%' => $processed])
                                .'</comment>'."\n"
                            );
                        } else {
                            $output->writeln(
                                '<comment>'.$translator->trans(
                                    'mautic.plugin.command.fetch.leads.events_executed_breakout',
                                    ['%updated%' => $updated, '%created%' => $created]
                                )
                                .'</comment>'."\n"
                            );
                        }
                    } else {
                        $output->writeln('<error>'.$translator->trans('mautic.plugin.command.fetch.leads.wrong.date').'</error>');
                    }
                }
            }

            if ($integrationObject !== null && method_exists($integrationObject, 'getCompanies') && isset($config['objects'])
                && in_array(
                    'company',
                    $config['objects']
                )
            ) {
                $updated = $created = $processed = 0;
                $output->writeln('<info>'.$translator->trans('mautic.plugin.command.fetch.companies', ['%integration%' => $integration]).'</info>');
                $output->writeln('<comment>'.$translator->trans('mautic.plugin.command.fetch.companies.starting').'</comment>');

                if (strtotime($startDate) > strtotime('-30 days')) {
                    $results = $integrationObject->getCompanies($params);
                    if (is_array($results)) {
                        list($justUpdated, $justCreated) = $results;
                        $updated += intval($justUpdated);
                        $created += intval($justCreated);
                    } else {
                        $processed += intval($results);
                    }

                    if ($processed) {
                        $output->writeln(
                            '<comment>'.$translator->trans('mautic.plugin.command.fetch.companies.events_executed', ['%events%' => $processed])
                            .'</comment>'."\n"
                        );
                    } else {
                        $output->writeln(
                                '<comment>'.$translator->trans(
                                    'mautic.plugin.command.fetch.companies.events_executed_breakout',
                                    ['%updated%' => $updated, '%created%' => $created]
                                )
                                .'</comment>'."\n"
                            );
                    }
                } else {
                    $output->writeln('<error>'.$translator->trans('mautic.plugin.command.fetch.leads.wrong.date').'</error>');
                }
            }

            if (isset($supportedFeatures) && in_array('push_leads', $supportedFeatures)) {
                $output->writeln('<info>'.$translator->trans('mautic.plugin.command.pushing.leads', ['%integration%' => $integration]).'</info>');
                $result  = $integrationObject->pushLeads($params);
                $errored = '?';
                $ignored = 0;

                if (4 === count($result)) {
                    list($updated, $created, $errored, $ignored) = $result;
                } elseif (3 === count($result)) {
                    list($updated, $created, $errored) = $result;
                } else {
                    $errored                 = '?';
                    list($updated, $created) = $result;
                }
                $output->writeln(
                    '<comment>'.$translator->trans(
                        'mautic.plugin.command.fetch.pushing.leads.events_executed',
                        [
                            '%updated%' => $updated,
                            '%created%' => $created,
                            '%errored%' => $errored,
                            '%ignored%' => $ignored,
                        ]
                    )
                    .'</comment>'."\n"
                );
            }

            return true;
        }
    }
}
