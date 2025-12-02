<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class MigrationCommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VersionProviderInterface $versionProvider,
        private GeneratedColumnsProviderInterface $generatedColumnsProvider,
        private Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => ['addGeneratedColumns'],
        ];
    }

    public function addGeneratedColumns(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        $output  = $event->getOutput();

        if ('doctrine:migrations:migrate' !== $command->getName()) {
            return;
        }

        if (!$this->generatedColumnsProvider->generatedColumnsAreSupported()) {
            $output->writeln('');
            $output->writeln("<comment>Your database version ({$this->versionProvider->getVersion()}) does not support generated columns. Upgrade at least to {$this->generatedColumnsProvider->getMinimalSupportedVersion()} to get the speed improvements.</comment>");
            $output->writeln('');

            return;
        }

        $generatedColumns   = $this->generatedColumnsProvider->getGeneratedColumns();
        $groupedByTableName = [];

        foreach ($generatedColumns as $generatedColumn) {
            if ($this->generatedColumnExistsInSchema($generatedColumn)) {
                continue;
            }

            $tableName = $generatedColumn->getTableName();

            if (!isset($groupedByTableName[$tableName])) {
                $groupedByTableName[$tableName] = [];
            }

            $groupedByTableName[$tableName][$generatedColumn->getColumnName()] = $generatedColumn;
        }

        foreach ($groupedByTableName as $tableName => $generatedColumns) {
            $query = "ALTER TABLE {$tableName} ".implode(', '.PHP_EOL, array_map(function (GeneratedColumnInterface $generatedColumn) {
                return $generatedColumn->getAddColumnSql();
            }, $generatedColumns));

            $this->executeAlterQuery($query, $tableName, 'adding generated columns', $output);

            $query = "ALTER TABLE {$tableName} ".implode(', '.PHP_EOL, array_map(function (GeneratedColumnInterface $generatedColumn) {
                return $generatedColumn->getAddIndexSql();
            }, $generatedColumns));

            $this->executeAlterQuery($query, $tableName, 'adding indices', $output);
        }
    }

    private function executeAlterQuery(string $query, string $tableName, string $comment, OutputInterface $output): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start($tableName, 'generated columns');

        $output->writeln('');
        $output->writeln("<info>++</info> Executing {$comment} for table <comment>{$tableName}</comment>");
        $output->writeln("<comment>-></comment> {$query}");

        $this->connection->executeStatement($query);

        $duration = (string) $stopwatch->stop($tableName);
        $output->writeln("<info>++</info> Execution finished ({$duration})");
        $output->writeln('');
    }

    private function generatedColumnExistsInSchema(GeneratedColumn $generatedColumn): bool
    {
        $tableColumns = $this->connection->createSchemaManager()->listTableColumns($generatedColumn->getTableName());

        if (isset($tableColumns[$generatedColumn->getColumnName()])) {
            return true;
        }

        return false;
    }
}
