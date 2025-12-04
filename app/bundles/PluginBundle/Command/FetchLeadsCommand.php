<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:integration:fetchleads',
    description: 'Fetch leads from integration.',
    aliases: [
        'mautic:integration:synccontacts',
    ]
)]
class FetchLeadsCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[\Symfony\Component\Console\Attribute\Option(name: '--integration', shortcut: '-i', description: 'Fetch leads from integration. Integration must be enabled and authorised.')]
        $integration = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--start-date', shortcut: '-d', description: 'Set start date for updated values.')]
        $startDate = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--end-date', shortcut: '-t', description: 'Set end date for updated values.')]
        $endDate = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--fetch-all', description: 'Get all CRM contacts whatever the date is. Should be used at instance initialization only')]
        bool $fetchAll = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--time-interval', shortcut: '-a', description: 'Send time interval to check updates on Salesforce, it should be a correct php formatted time interval in the past eg:(10 minutes)')]
        $timeInterval = null,
        #[\Symfony\Component\Console\Attribute\Option(name: '--limit', shortcut: '-l', description: 'Number of records to process when syncing objects')]
        int $limit = 100,
        #[\Symfony\Component\Console\Attribute\Option(name: '--force', shortcut: '-f', description: 'Force execution even if another process is assumed running.')]
        bool $force = false,
    ): int {
        $interval      = $timeInterval;
        $leadsExecuted = $contactsExecuted = null;

        // @TODO Since integration is mandatory it should really be turned into an agument, but that would not be B.C.
        if (!$integration) {
            throw new \RuntimeException('An integration must be specified');
        }

        $integrationObject = $this->integrationHelper->getIntegrationObject($integration);
        if (!$integrationObject instanceof UnifiedIntegrationInterface) {
            $availableIntegrations = array_filter($this->integrationHelper->getIntegrationObjects(),
                fn (UnifiedIntegrationInterface $availableIntegration) => $availableIntegration->isConfigured());
            throw new \RuntimeException(sprintf('The Integration "%s" is not one of the available integrations (%s)', $integration, implode(', ', array_keys($availableIntegrations))));
        }

        if (!$interval) {
            $interval = '15 minutes';
        }
        $startDate = !$startDate ? date('c', strtotime('-'.$interval)) : date('c', strtotime($startDate));
        $endDate   = !$endDate ? date('c') : date('c', strtotime($endDate));

        if (!$endDate) {
            $output->writeln(sprintf('<info>Invalid date rage given %s -> %s</info>', $startDate, $endDate));

            return 255;
        }

        $integrationObject = $this->integrationHelper->getIntegrationObject($integration);

        if (!$integrationObject->isAuthorized()) {
            $output->writeln(sprintf('<error>ERROR:</error> <info>'.$this->translator->trans('mautic.plugin.command.notauthorized').'</info>', $integration));

            return 255;
        }

        // Tell audit log to use integration name
        define('MAUTIC_AUDITLOG_USER', $integration);

        $config            = $integrationObject->mergeConfigToFeatureSettings();
        $supportedFeatures = $integrationObject->getIntegrationSettings()->getSupportedFeatures();

        defined('MAUTIC_CONSOLE_VERBOSITY') or define('MAUTIC_CONSOLE_VERBOSITY', $output->getVerbosity());

        if (!isset($config['objects'])) {
            $config['objects'] = [];
        }

        $params['start']    = $startDate;
        $params['end']      = $endDate;
        $params['limit']    = $limit;
        $params['fetchAll'] = $fetchAll;
        $params['output']   = $output;

        $integrationObject->setCommandParameters($params);

        // set this constant to ensure that all contacts have the same date modified time and date synced time to prevent a pull/push loop
        define('MAUTIC_DATE_MODIFIED_OVERRIDE', time());

        if (isset($supportedFeatures) && in_array('get_leads', $supportedFeatures)) {
            if (null !== $integrationObject && method_exists($integrationObject, 'getLeads') && isset($config['objects'])) {
                $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.fetch.leads', ['%integration%' => $integration]).'</info>');
                $output->writeln('<comment>'.$this->translator->trans('mautic.plugin.command.fetch.leads.starting').'</comment>');

                // Handle case when integration object are named "Contacts" and "Leads"
                $leadObjectName = 'Lead';
                if (in_array('Leads', $config['objects'])) {
                    $leadObjectName = 'Leads';
                }
                $contactObjectName = 'Contact';
                if (in_array(strtolower('Contacts'), array_map(fn ($i): string => strtolower($i), $config['objects']), true)) {
                    $contactObjectName = 'Contacts';
                }

                $updated = $created = $processed = 0;
                if (in_array($leadObjectName, $config['objects'])) {
                    $leadList = [];
                    $results  = $integrationObject->getLeads($params, null, $leadsExecuted, $leadList, $leadObjectName);
                    if (is_array($results)) {
                        [$justUpdated, $justCreated] = $results;
                        $updated += (int) $justUpdated;
                        $created += (int) $justCreated;
                    } else {
                        $processed += (int) $results;
                    }
                }
                if (in_array(strtolower($contactObjectName), array_map(fn ($i): string => strtolower($i), $config['objects']), true)) {
                    $output->writeln('');
                    $output->writeln('<comment>'.$this->translator->trans('mautic.plugin.command.fetch.contacts.starting').'</comment>');
                    $contactList = [];
                    $results     = $integrationObject->getLeads($params, null, $contactsExecuted, $contactList, $contactObjectName);
                    if (is_array($results)) {
                        [$justUpdated, $justCreated] = $results;
                        $updated += (int) $justUpdated;
                        $created += (int) $justCreated;
                    } else {
                        $processed += (int) $results;
                    }
                }

                $output->writeln('');

                if ($processed) {
                    $output->writeln(
                        '<comment>'.$this->translator->trans('mautic.plugin.command.fetch.leads.events_executed', ['%events%' => $processed])
                        .'</comment>'."\n"
                    );
                } else {
                    $output->writeln(
                        '<comment>'.$this->translator->trans(
                            'mautic.plugin.command.fetch.leads.events_executed_breakout',
                            ['%updated%' => $updated, '%created%' => $created]
                        )
                        .'</comment>'."\n"
                    );
                }
            }

            if (null !== $integrationObject && method_exists($integrationObject, 'getCompanies') && isset($config['objects'])
                && in_array(
                    'company',
                    $config['objects']
                )
            ) {
                $updated = $created = $processed = 0;
                $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.fetch.companies', ['%integration%' => $integration]).'</info>');
                $output->writeln('<comment>'.$this->translator->trans('mautic.plugin.command.fetch.companies.starting').'</comment>');

                $results = $integrationObject->getCompanies($params);
                if (is_array($results)) {
                    [$justUpdated, $justCreated] = $results;
                    $updated += (int) $justUpdated;
                    $created += (int) $justCreated;
                } else {
                    $processed += (int) $results;
                }
                $output->writeln('');
                if ($processed) {
                    $output->writeln(
                        '<comment>'.$this->translator->trans('mautic.plugin.command.fetch.companies.events_executed', ['%events%' => $processed])
                        .'</comment>'."\n"
                    );
                } else {
                    $output->writeln(
                        '<comment>'.$this->translator->trans(
                            'mautic.plugin.command.fetch.companies.events_executed_breakout',
                            ['%updated%' => $updated, '%created%' => $created]
                        )
                        .'</comment>'."\n"
                    );
                }
            }
        }

        if (isset($supportedFeatures) && in_array('push_leads', $supportedFeatures) && method_exists($integrationObject, 'pushLeads')) {
            $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.pushing.leads', ['%integration%' => $integration]).'</info>');
            $result  = $integrationObject->pushLeads($params);
            $ignored = 0;

            if (4 === count($result)) {
                [$updated, $created, $errored, $ignored] = $result;
            } elseif (3 === count($result)) {
                [$updated, $created, $errored] = $result;
            } else {
                $errored                 = '?';
                [$updated, $created]     = $result;
            }
            $output->writeln(
                '<comment>'.$this->translator->trans(
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

            if (in_array('push_companies', $supportedFeatures) && method_exists($integrationObject, 'pushCompanies')) {
                $output->writeln('<info>'.$this->translator->trans('mautic.plugin.command.pushing.companies', ['%integration%' => $integration]).'</info>');
                $result  = $integrationObject->pushCompanies($params);
                $ignored = 0;

                if (4 === count($result)) {
                    [$updated, $created, $errored, $ignored] = $result;
                } elseif (3 === count($result)) {
                    [$updated, $created, $errored] = $result;
                } else {
                    $errored                 = '?';
                    [$updated, $created]     = $result;
                }
                $output->writeln(
                    '<comment>'.$this->translator->trans(
                        'mautic.plugin.command.fetch.pushing.companies.events_executed',
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
        }

        return Command::SUCCESS;
    }
}
