<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Command;

use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: SyncCommand::NAME,
    description: 'Fetch objects from integration.'
)]
class SyncCommand
{
    public const NAME = 'mautic:integrations:sync';

    public function __construct(
        private SyncServiceInterface $syncService,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Argument(name: 'integration', description: 'Fetch objects from integration.')]
        ?string $integration,
        #[\Symfony\Component\Console\Attribute\Option(name: '--start-datetime', shortcut: '-t', mode: InputOption::VALUE_OPTIONAL, description: 'Set start date/time for updated values in UTC timezone.')]
        $startDatetime,
        #[\Symfony\Component\Console\Attribute\Option(name: '--end-datetime', mode: InputOption::VALUE_OPTIONAL, description: 'Set start date/time for updated values in UTC timezone.')]
        $endDatetime,
        #[\Symfony\Component\Console\Attribute\Option(name: '--mautic-object-id', mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, description: 'Provide specific Mautic object IDs you want to sync. If some object IDs are provided then the start/end dates have no effect. Example: --mautic-object-id=contact:12 --mautic-object-id=company:13')]
        array $mauticObjectId,
        #[\Symfony\Component\Console\Attribute\Option(name: '--integration-object-id', mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, description: 'Provide specific integration object IDs you want to sync. If some object IDs are provided then the start/end dates have no effect. It depends on each integration if this is supported. Example: --integration-object-id=Account:12 --integration-object-id=Lead:13')]
        array $integrationObjectId,
        #[\Symfony\Component\Console\Attribute\Option(name: '--first-time-sync', shortcut: '-f', mode: InputOption::VALUE_NONE, description: 'Notate if this is a first time sync where Mautic will sync existing objects instead of just tracked changes')]
        bool $firstTimeSync = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--disable-push', mode: InputOption::VALUE_NONE, description: 'Notate if the sync should execute only pushing items from Mautic to the integration')]
        bool $disablePush = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--disable-pull', mode: InputOption::VALUE_NONE, description: 'Notate if the sync should execute only pulling items from integration to the Mautic')]
        bool $disablePull = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--option', mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, description: 'Provide option pass to InputOptions Example: --option="type:1" --option="channel_id:1"')]
        array $option,
        #[\Symfony\Component\Console\Attribute\Option(name: '--disable-activity-push', mode: InputOption::VALUE_NONE, description: 'Notate if the sync should disable the activities sync if the integration supports it')]
        bool $disableActivityPush = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--env', shortcut: '-e', mode: InputOption::VALUE_OPTIONAL, description: 'Environment')]
        ?string $env = null,
        SymfonyStyle $io,
        InputInterface $input,
    ): int {
        try {
            $inputOptions = new InputOptionsDAO(array_merge($input->getArguments(), $input->getOptions()));
        } catch (InvalidValueException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        try {
            defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS') or define('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS', $inputOptions->getIntegration());

            // Tell audit log to use integration name rather than "System"
            defined('MAUTIC_AUDITLOG_USER') or define('MAUTIC_AUDITLOG_USER', $inputOptions->getIntegration());

            $this->syncService->processIntegrationSync($inputOptions);
        } catch (\Throwable $e) {
            if ('dev' === $env || (defined('MAUTIC_ENV') && MAUTIC_ENV === 'dev')) {
                throw $e;
            }

            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return Command::SUCCESS;
    }
}
