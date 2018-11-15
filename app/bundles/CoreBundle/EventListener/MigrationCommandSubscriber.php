<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Doctrine\DBAL\Driver\Connection;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class MigrationCommandSubscriber extends CommonSubscriber
{
    /**
     * @var VersionProviderInterface
     */
    private $versionProvider;

    /**
     * @var GeneratedColumnsProviderInterface
     */
    private $generatedColumnsProvider;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param VersionProviderInterface          $versionProvider
     * @param GeneratedColumnsProviderInterface $generatedColumnsProvider
     * @param Connection                        $connection
     */
    public function __construct(
        VersionProviderInterface $versionProvider,
        GeneratedColumnsProviderInterface $generatedColumnsProvider,
        Connection $connection
    ) {
        $this->versionProvider          = $versionProvider;
        $this->generatedColumnsProvider = $generatedColumnsProvider;
        $this->connection               = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => ['addGeneratedColumns'],
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function addGeneratedColumns(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $output  = $event->getOutput();

        if ($command->getName() !== 'doctrine:migrations:migrate') {
            return;
        }

        if (!$this->generatedColumnsProvider->generatedColumnsAreSupported()) {
            $output->writeln('');
            $output->writeln("<comment>Your database version ({$this->versionProvider->fetchVersion()}) does not support generated columns. Upgrade at least to {$this->generatedColumnsProvider->getMinimalSupportedVersion()} to get the speed improvements.</comment>");
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

            $this->connection->query($generatedColumn->getAlterTableSql());

            $duration = (string) $stopwatch->stop($generatedColumn->getColumnName());
            $output->writeln("<info>++</info> generated column added ({$duration})");
            $output->writeln('');
        }
    }

    /**
     * @param GeneratedColumn $generatedColumn
     *
     * @return bool
     */
    private function generatedColumnExistsInSchema(GeneratedColumn $generatedColumn)
    {
        $tableColumns = $this->connection->getSchemaManager()->listTableColumns($generatedColumn->getTableName());

        if (isset($tableColumns[$generatedColumn->getColumnName()])) {
            return true;
        }

        return false;
    }
}
