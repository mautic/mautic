<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class MigrationCommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VersionProviderInterface $versionProvider,
        private GeneratedColumnsProviderInterface $generatedColumnsProvider,
        private Connection $connection
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['addGeneratedColumns'],
        ];
    }

    public function addGeneratedColumns(ConsoleCommandEvent $event): void
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

        $stopwatch        = new Stopwatch();
        $generatedColumns = $this->generatedColumnsProvider->getGeneratedColumns();

        foreach ($generatedColumns as $generatedColumn) {
            if ($this->generatedColumnExistsInSchema($generatedColumn)) {
                continue;
            }

            $stopwatch->start($generatedColumn->getColumnName(), 'generated columns');

            $output->writeln('');
            $output->writeln("<info>++</info> adding generated column <comment>{$generatedColumn->getColumnName()}</comment>");
            $output->writeln("<comment>-></comment> {$generatedColumn->getAlterTableSql()}");

            $this->connection->executeQuery($generatedColumn->getAlterTableSql());

            $duration = (string) $stopwatch->stop($generatedColumn->getColumnName());
            $output->writeln("<info>++</info> generated column added ({$duration})");
            $output->writeln('');
        }
    }

    private function generatedColumnExistsInSchema(GeneratedColumn $generatedColumn): bool
    {
        $tableColumns = $this->connection->getSchemaManager()->listTableColumns($generatedColumn->getTableName());

        if (isset($tableColumns[$generatedColumn->getColumnName()])) {
            return true;
        }

        return false;
    }
}
